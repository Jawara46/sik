const db = require("./db_config");
const nisn = "0012345001";

const query = `
  SELECT s.*, sch.nama_sekolah, sch.enable_wa_auto_respond, m.name as major_name
  FROM students s
  JOIN schools sch ON s.school_id = sch.id
  LEFT JOIN majors m ON s.major_id = m.id
  WHERE s.nisn = ? LIMIT 1
`;

db.query(query, [nisn], (err, results) => {
  if (err) {
    console.error("Query Error:", err);
    process.exit(1);
  }
  console.log("Query Results:", JSON.stringify(results, null, 2));
  process.exit(0);
});
