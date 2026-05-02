const {
  default: makeWASocket,
  DisconnectReason,
  MessageType,
  MessageOptions,
  Mimetype,
  BufferJSON,
  AnyMessageContent,
  delay,
  fetchLatestBaileysVersion,
  isJidBroadcast,
  makeCacheableSignalKeyStore,
  makeInMemoryStore,
  MessageRetryMap,
  msgRetryCounterMap,
} = require("baileys");
const { useMultiFileAuthState } = require("baileys");
const express = require("express");
const qrcode = require("qrcode");
const { Boom } = require("@hapi/boom");
const log = (pino = require("pino"));
const { session } = { session: "auth_info" };
const path = require("path");
const fs = require("fs");
const http = require("http");
const https = require("https");
const fileUpload = require("express-fileupload");
const cors = require("cors");
const bodyParser = require("body-parser");
const fsExtra = require("fs-extra");
const db = require("./db_config");
const app = express();
const formater = require("./phonFormater");

app.use(cors());
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));
const server = require("http").createServer(app);
const io = require("socket.io")(server);
const port = process.env.PORT || 3000;
const host = process.env.WAPI_HOST || "127.0.0.1";
const portalUrl = process.env.APP_PORTAL_URL || "http://localhost:8888/sik/public";

app.use("/assets", express.static(__dirname + "/client/assets"));

app.get("/", (req, res) => {
  res.sendFile("./client/index.html", {
    root: __dirname,
  });
});

const emptyFolder = async (folderPath) => {
  try {
    await fsExtra.emptyDir(folderPath);
    console.log("Done!");
  } catch (err) {
    console.log(err);
  }
};

let sock;
let myQr;
let open = "close";
let jeda = 10000; // 10 detik
let myToken = "tokenwapi"; // token Whatsapp

const parseAutoRespondCommand = (input) => {
  const text = String(input || "").trim().toUpperCase();

  if (text === "HELP" || text === "MENU") {
    return { type: "help" };
  }

  const match = text.match(/^#?(CEK(?:NILAI)?|STATUS)\s*[:\-]?\s*(\d{6,20})$/);
  if (!match) {
    return null;
  }

  return {
    type: "lookup",
    nisn: match[2],
  };
};

const buildHelpMessage = () =>
  [
    "🤖 *Bantuan WhatsApp Bot*",
    "",
    "Gunakan salah satu format berikut:",
    "*CEK 0012345678*",
    "*#CEKNILAI 0012345678*",
    "*STATUS 0012345678*",
  ].join("\n");

const logAutoRespond = (payload) => {
  db.query(
    "INSERT INTO wa_auto_respond_logs (school_id, sender_number, sender_name, nisn_queried, student_id, request_message, response_message, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
    [
      payload.school_id,
      payload.sender_number,
      payload.sender_name,
      payload.nisn_queried,
      payload.student_id,
      payload.request_message,
      payload.response_message,
      payload.status,
    ]
  );
};

async function connectToWhatsApp() {
  const { state, saveCreds } = await useMultiFileAuthState("./auth_info");
  let { version } = await fetchLatestBaileysVersion();
  sock = makeWASocket({
    auth: state,
    logger: log({ level: "silent" }),
    version,
    shouldIgnoreJid: (jid) => isJidBroadcast(jid),
  });

  sock.ev.on("creds.update", saveCreds);

  // Listener untuk pesan masuk (Auto-Respond)
  sock.ev.on("messages.upsert", async (m) => {
    console.log("DEBUG: Upsert event received:", m.type);
    if (m.type !== "notify") return;
    
    for (const msg of m.messages) {
      console.log("DEBUG: Processing msg object:", JSON.stringify(msg, null, 2));
      if (!msg.message) {
        console.log("DEBUG: Skiping because !msg.message");
        continue;
      }
      if (msg.key.fromMe) {
        console.log("DEBUG: Skiping because msg.key.fromMe is true");
        continue;
      }

      const remoteJid = msg.key.remoteJid;
      if (!remoteJid.endsWith("@s.whatsapp.net") && !remoteJid.endsWith("@lid")) {
        console.log("DEBUG: Skiping because remoteJid is not personal:", remoteJid);
        continue;
      }

      let senderNumber = "";
      if (remoteJid.endsWith("@s.whatsapp.net")) {
        senderNumber = remoteJid.split("@")[0];
      } else if (msg.key.senderPn) {
        senderNumber = msg.key.senderPn.split("@")[0];
      } else {
        senderNumber = "unknown";
      }

      const senderName = msg.pushName || "Siswa";
      const body = msg.message.conversation || 
                   msg.message.extendedTextMessage?.text || 
                   "";

      const text = body.trim().toUpperCase();
      console.log(`DEBUG: Incoming [${senderNumber}]: ${text}`);

      const command = parseAutoRespondCommand(body);

      if (command?.type === "lookup") {
        const nisn = command.nisn;
        const query = `
          SELECT s.*, sch.nama_sekolah, sch.enable_wa_auto_respond, m.name as major_name
          FROM students s
          JOIN schools sch ON s.school_id = sch.id
          LEFT JOIN majors m ON s.major_id = m.id
          WHERE s.nisn = ? LIMIT 1
        `;

        db.query(query, [nisn], async (err, results) => {
          if (err) {
            console.error("Database error:", err);
            const errorMessage = "⚠️ Sistem sedang mengalami gangguan saat membaca data kelulusan. Silakan coba lagi beberapa saat lagi.";
            await sock.sendMessage(remoteJid, { text: errorMessage });
            logAutoRespond({
              school_id: null,
              sender_number: senderNumber,
              sender_name: senderName,
              nisn_queried: nisn,
              student_id: null,
              request_message: body,
              response_message: errorMessage,
              status: "error",
            });
            return;
          }

          if (results.length > 0) {
            const student = results[0];
            if (!student.enable_wa_auto_respond) {
              const disabledMessage = "ℹ️ Layanan cek kelulusan via WhatsApp saat ini belum diaktifkan oleh sekolah. Silakan gunakan portal siswa atau hubungi admin sekolah.";
              await sock.sendMessage(remoteJid, { text: disabledMessage });
              logAutoRespond({
                school_id: student.school_id,
                sender_number: senderNumber,
                sender_name: senderName,
                nisn_queried: nisn,
                student_id: student.id,
                request_message: body,
                response_message: disabledMessage,
                status: "error",
              });
              return;
            }

            let replyMessage = "";
            if (student.status === "Lulus") {
              replyMessage = `🎓 *Pengumuman Kelulusan*\n━━━━━━━━━━━━━━━\n\nHalo *${student.name}* 👋\n\n✅ Status Anda: *LULUS*\n🏫 Sekolah: ${student.nama_sekolah}\n📚 Jurusan: ${student.major_name || '-'}\n\nSelamat! Silakan login ke portal siswa untuk mengunduh dokumen SKL dan Transkrip.\n\n🔗 Portal: ${portalUrl}\n👤 Login: NISN Anda\n🔑 Password: Tanggal Lahir (DDMMYYYY)\n\n_Pesan otomatis - SIK-T_`;
            } else if (student.status === "Tidak Lulus") {
              replyMessage = `📋 *Pengumuman Kelulusan*\n━━━━━━━━━━━━━━━\n\nHalo *${student.name}* 👋\n\nStatus Anda: *TIDAK LULUS*\n🏫 Sekolah: ${student.nama_sekolah}\n📚 Jurusan: ${student.major_name || '-'}\n\nUntuk informasi lebih lanjut, silakan hubungi pihak sekolah secara langsung.\n\n_Pesan otomatis - SIK-T_`;
            } else {
              replyMessage = `⏳ *Pengumuman Kelulusan*\n━━━━━━━━━━━━━━━\n\nHalo *${student.name}* 👋\n\nStatus kelulusan Anda masih dalam proses (Pending).\nSilakan cek kembali nanti atau hubungi pihak sekolah.\n\n_Pesan otomatis - SIK-T_`;
            }

            await sock.sendMessage(remoteJid, { text: replyMessage });
            logAutoRespond({
              school_id: student.school_id,
              sender_number: senderNumber,
              sender_name: senderName,
              nisn_queried: nisn,
              student_id: student.id,
              request_message: body,
              response_message: replyMessage,
              status: "replied",
            });
          } else {
            const helpMessage = `❌ *NISN tidak ditemukan*\n\nMaaf, NISN *${nisn}* tidak terdaftar. Pastikan nomor yang Anda masukkan benar.\n\nContoh: *CEK 0012345678*`;
            await sock.sendMessage(remoteJid, { text: helpMessage });
            logAutoRespond({
              school_id: null,
              sender_number: senderNumber,
              sender_name: senderName,
              nisn_queried: nisn,
              student_id: null,
              request_message: body,
              response_message: helpMessage,
              status: "not_found",
            });
          }
        });
      } else if (command?.type === "help") {
        await sock.sendMessage(remoteJid, { text: buildHelpMessage() });
      }
    }
  });

  sock.ev.on("connection.update", async (update) => {
    const { connection, lastDisconnect, qr } = update;
    if (connection === "close") {
      let reason = new Boom(lastDisconnect.error).output.statusCode;
      if (reason === DisconnectReason.badSession) {
        console.log(`Bad Session File, Scan Again`);
        sock.logout();
      } else if (reason === DisconnectReason.connectionClosed) {
        console.log("Connection closed, reconnecting....");
        connectToWhatsApp();
      } else if (reason === DisconnectReason.connectionLost) {
        console.log("Connection Lost, reconnecting...");
        connectToWhatsApp();
      } else if (reason === DisconnectReason.connectionReplaced) {
        console.log("Connection Replaced");
        sock.logout();
      } else if (reason === DisconnectReason.loggedOut) {
        emptyFolder("./auth_info");
        console.log(`Logged Out, Scan Again.`);
        connectToWhatsApp();
      } else if (reason === DisconnectReason.restartRequired) {
        console.log("Restart Required...");
        connectToWhatsApp();
      } else if (reason === DisconnectReason.timedOut) {
        console.log("Connection TimedOut...");
        connectToWhatsApp();
      } else {
        sock.end(`Unknown Error: ${reason}`);
      }
      open = "close";
    } else if (connection === "open") {
      console.log("Terhubung ke WhatsApp!");
      open = "open";
    } else if (qr) {
      qrcode.toDataURL(qr, (err, url) => {
        if (!err) myQr = url;
      });
    }
  });
}

app.post("/get-qr", async (req, res) => {
  if (req.body.token === myToken) {
    res.json({ response: myQr, status: open === "open" ? "3" : "1" });
  } else {
    res.status(403).json({ status: "0", response: "Token invalid" });
  }
});

app.get("/health", async (req, res) => {
  res.json({
    status: true,
    gateway: open === "open" ? "CONNECTED" : "DISCONNECTED",
    has_qr: Boolean(myQr),
    whatsapp_user: sock?.user?.id || null,
    node_version: process.version,
  });
});

const isConnected = () => sock?.user;

app.post("/kirim-pesan", async (req, res) => {
  if (req.body.token !== myToken) return res.status(403).json({ status: false, response: "Token invalid" });
  const { number, message } = req.body;
  if (!number) return res.status(400).json({ status: false, response: "Nomor WA kosong!" });

  try {
    const num = formater(number);
    const jid = "62" + num.substring(1) + "@s.whatsapp.net";
    if (isConnected()) {
      const exists = await sock.onWhatsApp(jid);
      if (exists && exists.length > 0 && exists[0].exists) {
        const targetJid = exists[0].jid;
        console.log("DEBUG: Sending to resolved JID:", targetJid);
        const result = await sock.sendMessage(targetJid, { text: message });
        res.json({ status: true, response: result });
      } else {
        res.status(404).json({ status: false, response: "Nomor tidak terdaftar di WhatsApp" });
      }
    } else {
      res.status(500).json({ status: false, response: "WA belum terhubung" });
    }
  } catch (err) {
    console.error("ERROR kirim-pesan:", err.message);
    res.status(500).json({ status: false, response: err.message });
  }
});

app.post("/logout", async (req, res) => {
  if (req.body.token !== myToken) return res.status(403).json({ status: false, response: "Token invalid" });
  try {
    if (sock) await sock.logout();
    await emptyFolder("./auth_info");
    open = "close";
    myQr = null;
    setTimeout(() => connectToWhatsApp().catch(null), 1500);
    res.json({ status: true, response: "Logged out" });
  } catch (err) {
    res.json({ status: true, response: "Cleaned up" });
  }
});

connectToWhatsApp().catch((err) => console.log("unexpected error: " + err));

server.listen(port, host, () => console.log(`Server Berjalan pada ${host}:${port}`));
