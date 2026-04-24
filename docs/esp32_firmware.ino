/*
 * ================================================================
 * SmartKlon Mart — ESP32 Firmware Terintegrasi
 * UHF RFID Scanner (Raw Byte) ke Laravel API via HTTP POST
 * 
 * Serial Monitor: Set baud rate ke 115200
 * ================================================================
 */

#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>
#include <HardwareSerial.h>

// -------------------------------------------------------
// KONFIGURASI JARINGAN & SERVER
// -------------------------------------------------------
const char* WIFI_SSID     = "Redmi Note 14 Pro 5G";
const char* WIFI_PASSWORD = "hapeberkanih";
const char* SERVER_URL    = "http://10.47.121.253:8000/api/rfid/scan";

// -------------------------------------------------------
// KONFIGURASI HARDWARE
// -------------------------------------------------------
#define RFID_RX_PIN    16
#define RFID_TX_PIN    17
#define RFID_BAUDRATE  57600
#define BUZZER_PIN     15
#define LCD_ADDR       0x27
#define LCD_COLS       16
#define LCD_ROWS       2

// -------------------------------------------------------
// KONSTANTA PEMBACAAN RFID
// -------------------------------------------------------
#define READ_TIMEOUT   100
#define MAX_RESPONSE   128
#define HEADER_LEN     4
#define CHECKSUM_LEN   2
#define STATUS_OK      0x00
#define CMD_EXPECTED   0xEE

// -------------------------------------------------------
// OBJEK GLOBAL
// -------------------------------------------------------
HardwareSerial readerSerial(2);
LiquidCrystal_I2C lcd(LCD_ADDR, LCD_COLS, LCD_ROWS);
bool wifiConnected = false;
int scanCount = 0; // Counter total scan sejak boot

// -------------------------------------------------------
// HELPER — Garis pemisah Serial
// -------------------------------------------------------
void printLine(char c = '-', int len = 50) {
    for (int i = 0; i < len; i++) Serial.print(c);
    Serial.println();
}

// -------------------------------------------------------
// FUNGSI PEMBACAAN BYTE PRESISI
// -------------------------------------------------------
bool readExact(HardwareSerial &s, uint8_t *buf, size_t len, unsigned long timeoutMs) {
    unsigned long start = millis();
    size_t i = 0;
    while (i < len) {
        if (s.available()) {
            buf[i++] = (uint8_t)s.read();
            start = millis();
        } else if (millis() - start > timeoutMs) {
            return false;
        }
    }
    return true;
}

// -------------------------------------------------------
// FUNGSI NOTIFIKASI AUDIO
// -------------------------------------------------------
void beepSuccess() {
    // 1 beep panjang = berhasil diproses
    digitalWrite(BUZZER_PIN, HIGH);
    delay(300);
    digitalWrite(BUZZER_PIN, LOW);
}

void beepError() {
    // 3 beep cepat = error server / tag tidak ada
    for (int i = 0; i < 3; i++) {
        digitalWrite(BUZZER_PIN, HIGH);
        delay(80);
        digitalWrite(BUZZER_PIN, LOW);
        delay(80);
    }
}

void beepIdle() {
    // 1 beep singkat = scanner idle, tidak diproses
    digitalWrite(BUZZER_PIN, HIGH);
    delay(60);
    digitalWrite(BUZZER_PIN, LOW);
}

// -------------------------------------------------------
// FUNGSI KONEKSI WIFI
// -------------------------------------------------------
void connectWiFi() {
    printLine('=');
    Serial.print("[WiFi] Menghubungkan ke: ");
    Serial.println(WIFI_SSID);
    printLine();

    WiFi.mode(WIFI_STA);
    WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

    int attempts = 0;
    while (WiFi.status() != WL_CONNECTED && attempts < 30) {
        delay(500);
        Serial.print(".");
        attempts++;
    }
    Serial.println();

    if (WiFi.status() == WL_CONNECTED) {
        wifiConnected = true;
        printLine();
        Serial.println("[WiFi] STATUS   : TERHUBUNG ✓");
        Serial.print("[WiFi] IP ESP32 : ");
        Serial.println(WiFi.localIP().toString());
        Serial.print("[WiFi] Signal   : ");
        Serial.print(WiFi.RSSI());
        Serial.println(" dBm");
        Serial.print("[WiFi] Server   : ");
        Serial.println(SERVER_URL);
        printLine('=');

        lcd.clear();
        lcd.setCursor(0, 0);
        lcd.print("WiFi Terhubung!");
        lcd.setCursor(0, 1);
        lcd.print(WiFi.localIP().toString());
        delay(2000);

        lcd.clear();
        lcd.setCursor(0, 0);
        lcd.print("Sistem Siap...");
        lcd.setCursor(0, 1);
        lcd.print("Menunggu Tag...");
    } else {
        printLine();
        Serial.println("[WiFi] STATUS   : GAGAL TERHUBUNG ✗");
        Serial.println("[WiFi] Cek SSID dan Password!");
        printLine('=');

        lcd.clear();
        lcd.setCursor(0, 0);
        lcd.print("WiFi Gagal!");
        lcd.setCursor(0, 1);
        lcd.print("Cek Konfigurasi");
        beepError();
    }
}

// -------------------------------------------------------
// FUNGSI PENGIRIMAN DATA KE SERVER
// -------------------------------------------------------
void processEpc(String epc) {
    scanCount++;
    
    Serial.println();
    printLine('=');
    Serial.print("[SCAN #");
    Serial.print(scanCount);
    Serial.print("] ");
    Serial.println(millis() / 1000.0, 2);
    printLine();
    Serial.print("[RFID] EPC     : ");
    Serial.println(epc);
    Serial.print("[RFID] Panjang : ");
    Serial.print(epc.length());
    Serial.println(" karakter");
    
    // Tampilkan di LCD
    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("Mengirim...");
    lcd.setCursor(0, 1);
    if (epc.length() > 16) {
        lcd.print(epc.substring(0, 13) + "...");
    } else {
        lcd.print(epc);
    }

    // Buat JSON payload
    StaticJsonDocument<128> doc;
    doc["epc"] = epc;
    String payload;
    serializeJson(doc, payload);

    printLine();
    Serial.print("[HTTP] Endpoint : ");
    Serial.println(SERVER_URL);
    Serial.print("[HTTP] Payload  : ");
    Serial.println(payload);

    // Kirim HTTP POST
    HTTPClient http;
    http.begin(SERVER_URL);
    http.addHeader("Content-Type", "application/json");
    http.addHeader("Accept", "application/json");
    http.setTimeout(5000);

    unsigned long reqStart = millis();
    int httpCode = http.POST(payload);
    unsigned long reqDuration = millis() - reqStart;

    printLine();
    Serial.print("[HTTP] Status   : ");
    Serial.println(httpCode);
    Serial.print("[HTTP] Durasi   : ");
    Serial.print(reqDuration);
    Serial.println(" ms");

    // -------------------------------------------------------
    // Proses respons berdasarkan HTTP status code
    //
    // 200 = Transaksi berhasil (batch_in / single_in / out / check)
    // 202 = Scanner IDLE — request diterima tapi tidak diproses
    // 404 = Tag tidak dikenal (mode out: tag belum pernah didaftar)
    // 409 = Tag konflik (terdaftar ke produk lain)
    // 422 = Tag sudah berstatus out (tidak bisa diambil 2x)
    // -1  = Timeout / tidak ada koneksi ke server
    // -------------------------------------------------------
    if (httpCode > 0) {
        String responseBody = http.getString();
        Serial.print("[HTTP] Response : ");
        Serial.println(responseBody);

        // Parse JSON response
        StaticJsonDocument<256> resp;
        DeserializationError parseErr = deserializeJson(resp, responseBody);

        String statusStr = !parseErr ? resp["status"].as<String>() : "";
        String msgStr    = !parseErr ? resp["message"].as<String>() : "Response tidak valid";

        Serial.print("[JSON] status   : ");
        Serial.println(statusStr);
        Serial.print("[JSON] message  : ");
        Serial.println(msgStr);
        printLine();

        if (httpCode == 200) {
            // ✅ Transaksi benar-benar disimpan ke database
            Serial.println("[HASIL] ✅ SUKSES — Data tersimpan di database");
            
            String mode = !parseErr ? resp["mode"].as<String>() : "";
            String tagSt = !parseErr ? resp["tag_status"].as<String>() : "";
            if (mode.length()) {
                Serial.print("[HASIL] Mode    : ");
                Serial.println(mode);
            }
            if (tagSt.length()) {
                Serial.print("[HASIL] Tag     : ");
                Serial.println(tagSt);
            }

            lcd.clear();
            lcd.setCursor(0, 0);
            lcd.print("SUKSES! Tercatat");
            lcd.setCursor(0, 1);
            if (msgStr.length() > 16) msgStr = msgStr.substring(0, 16);
            lcd.print(msgStr);
            beepSuccess();

        } else if (httpCode == 202) {
            // ⏸ Scanner idle — TIDAK DIPROSES, TIDAK ADA YANG TERSIMPAN
            // Ini yang menyebabkan LCD bilang "sukses" tapi stok tidak berubah!
            Serial.println("[HASIL] ⏸ IDLE — Scanner sedang idle, tidak ada yang tersimpan!");
            Serial.println("[HASIL] → Aktifkan mode di web: Batch Masuk / Tombol +1 Unit");

            lcd.clear();
            lcd.setCursor(0, 0);
            lcd.print("Scanner IDLE!");
            lcd.setCursor(0, 1);
            lcd.print("Set Mode di Web");
            beepIdle();

        } else if (httpCode == 404) {
            // Tag asing yang belum pernah didaftarkan
            Serial.println("[HASIL] ❌ 404 — Tag tidak dikenal oleh sistem");
            Serial.println("[HASIL] → Daftarkan dulu via mode Batch Masuk / +1 Unit");

            lcd.clear();
            lcd.setCursor(0, 0);
            lcd.print("Tag Asing!");
            lcd.setCursor(0, 1);
            lcd.print("Daftar dulu!");
            beepError();

        } else if (httpCode == 409) {
            // Tag terdaftar ke produk lain
            Serial.println("[HASIL] ⚠ 409 — Tag sudah terdaftar ke produk LAIN");
            Serial.print("[HASIL] → ");
            Serial.println(msgStr);

            lcd.clear();
            lcd.setCursor(0, 0);
            lcd.print("Tag Produk Lain!");
            lcd.setCursor(0, 1);
            lcd.print("Cek di Web!");
            beepError();

        } else if (httpCode == 422) {
            // Tag sudah out sebelumnya
            Serial.println("[HASIL] ⚠ 422 — Tag sudah berstatus keluar sebelumnya");

            lcd.clear();
            lcd.setCursor(0, 0);
            lcd.print("Sudah Keluar!");
            lcd.setCursor(0, 1);
            lcd.print("Scan lain!");
            beepError();

        } else {
            // HTTP error lainnya (500, 503, dll)
            Serial.print("[HASIL] ✗ HTTP Error lain: ");
            Serial.println(httpCode);

            lcd.clear();
            lcd.setCursor(0, 0);
            lcd.print("Server Error!");
            lcd.setCursor(0, 1);
            lcd.print("Kode: " + String(httpCode));
            beepError();
        }

    } else {
        // httpCode -1 / negatif = koneksi ke server gagal sama sekali
        Serial.println("[HTTP] ✗ KONEKSI GAGAL — Server tidak terjangkau!");
        Serial.println("[HTTP] Pastikan server menyala & IP benar");
        Serial.print("[HTTP] Error code: ");
        Serial.println(httpCode);

        lcd.clear();
        lcd.setCursor(0, 0);
        lcd.print("Gagal Kirim!");
        lcd.setCursor(0, 1);
        lcd.print("Server Mati?");
        beepError();
    }

    http.end();
    printLine('=');

    // Bersihkan buffer agar tidak double-read
    delay(2000);
    while (readerSerial.available()) readerSerial.read();

    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("Sistem Siap...");
    lcd.setCursor(0, 1);
    lcd.print("Menunggu Tag...");
}

// -------------------------------------------------------
// SETUP
// -------------------------------------------------------
void setup() {
    Serial.begin(115200);
    delay(300);

    printLine('=');
    Serial.println("  SmartKlon Mart — ESP32 Firmware v2.0");
    Serial.println("  RFID -> HTTP POST -> Laravel API");
    printLine('=');

    readerSerial.begin(RFID_BAUDRATE, SERIAL_8N1, RFID_RX_PIN, RFID_TX_PIN);
    Serial.print("[HW] RFID Serial : RX=");
    Serial.print(RFID_RX_PIN);
    Serial.print(", TX=");
    Serial.print(RFID_TX_PIN);
    Serial.print(", Baud=");
    Serial.println(RFID_BAUDRATE);

    pinMode(BUZZER_PIN, OUTPUT);
    digitalWrite(BUZZER_PIN, LOW);
    Serial.print("[HW] Buzzer PIN  : ");
    Serial.println(BUZZER_PIN);

    Wire.begin();
    lcd.init();
    lcd.backlight();
    lcd.setCursor(0, 0);
    lcd.print("SmartKlon v2.0");
    lcd.setCursor(0, 1);
    lcd.print("Inisialisasi...");
    Serial.println("[HW] LCD         : OK");

    connectWiFi();

    Serial.println("[SYS] Setup selesai. Menunggu tag RFID...");
    printLine('=');
}

// -------------------------------------------------------
// MAIN LOOP
// -------------------------------------------------------
void loop() {
    // Cek koneksi WiFi
    if (WiFi.status() != WL_CONNECTED) {
        if (wifiConnected) {
            wifiConnected = false;
            Serial.println("[WiFi] TERPUTUS — Mencoba reconnect...");
            lcd.clear();
            lcd.setCursor(0, 0);
            lcd.print("WiFi Terputus!");
            lcd.setCursor(0, 1);
            lcd.print("Reconnecting...");
            connectWiFi();
        }
        return;
    }

    // Tunggu data dari RFID reader
    if (!readerSerial.available()) return;

    // Baca raw byte frame dari reader
    uint8_t resp[MAX_RESPONSE];
    size_t  respLen = 0;

    if (!readExact(readerSerial, &resp[0], 1, READ_TIMEOUT)) return;

    const uint8_t dataLen = resp[0];
    respLen = 1 + dataLen;

    if (respLen > MAX_RESPONSE) {
        Serial.println("[RFID] Frame terlalu panjang, dibuang.");
        for (uint8_t j = 0; j < dataLen && readerSerial.available(); ++j) readerSerial.read();
        return;
    }

    if (!readExact(readerSerial, &resp[1], dataLen, READ_TIMEOUT)) {
        Serial.println("[RFID] Timeout baca byte lanjutan.");
        return;
    }

    if (respLen < (HEADER_LEN + CHECKSUM_LEN)) return;

    const uint8_t command = resp[2];
    const uint8_t status  = resp[3];

    if (status != STATUS_OK || command != CMD_EXPECTED) {
        // Frame valid tapi bukan tag read (bisa ack, heartbeat, dll)
        return;
    }

    const int payloadLen = respLen - HEADER_LEN - CHECKSUM_LEN;
    if (payloadLen <= 0) return;

    // Konversi byte array ke HEX string
    const uint8_t *tag = &resp[HEADER_LEN];
    String tagID = "";
    for (int i = 0; i < payloadLen; i++) {
        if (tag[i] < 16) tagID += "0";
        tagID += String(tag[i], HEX);
    }
    tagID.toUpperCase();

    // Kirim ke server
    processEpc(tagID);
}
