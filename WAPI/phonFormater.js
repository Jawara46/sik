const formater = function formatPhoneNumber(phone) {
  // Hapus semua karakter yang bukan angka
  let cleanedNumber = phone.replace(/\D/g, "");

  // Jika nomor diawali dengan "62" atau "+62", ubah menjadi "0"
  if (cleanedNumber.startsWith("62")) {
    cleanedNumber = "0" + cleanedNumber.slice(2);
  }
  return cleanedNumber;
};

module.exports = formater;
