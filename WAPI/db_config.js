const fs = require("fs");
const mysql = require("mysql");

const defaultSocketPath = process.env.DB_SOCKET || "/Applications/MAMP/tmp/mysql/mysql.sock";
const useSocket = typeof defaultSocketPath === "string" && defaultSocketPath !== "" && fs.existsSync(defaultSocketPath);

const config = {
  user: process.env.DB_USERNAME || "root",
  password: process.env.DB_PASSWORD || "root",
  database: process.env.DB_DATABASE || "sik",
  charset: "utf8mb4",
};

if (useSocket) {
  config.socketPath = defaultSocketPath;
} else {
  config.host = process.env.DB_HOST || "127.0.0.1";
  config.port = Number(process.env.DB_PORT || 3306);
}

const pool = mysql.createPool(config);

pool.getConnection(function (err, connection) {
  if (err) {
    console.error("WAPI database pool connection failed:", err.message);
    throw err;
  }

  console.log(
    useSocket
      ? `database pool connected via socket: ${defaultSocketPath}`
      : `database pool connected via tcp: ${config.host}:${config.port}`
  );
  connection.release();
});

pool.on("error", function (err) {
  console.error("WAPI database error:", err.message);
});

module.exports = pool;
