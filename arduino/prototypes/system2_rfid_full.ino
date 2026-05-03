#include <SPI.h>
#include <MFRC522.h>
#include <Servo.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>

#define RST_PIN     9
#define SS_PIN      10
#define SERVO_PIN   6
#define PIR_PIN     7
#define BUZZER_PIN  8
#define GREEN_LED   4
#define RED_LED     5

MFRC522 rfid(SS_PIN, RST_PIN);
Servo kapi;
LiquidCrystal_I2C lcd(0x27, 16, 2);

String yetkiliKartlar[] = {
  "1D 1C F7 04"
};
int kartSayisi = 1;

bool kapiAcik = false;
unsigned long kapiAcilmaZamani = 0;
unsigned long sonHareketZamani = 0;
const int KAPI_BEKLEME = 3000;
const int HAREKET_BEKLEME = 5000;

void setup() {
  Serial.begin(9600);
  pinMode(GREEN_LED, OUTPUT);
  pinMode(RED_LED, OUTPUT);
  pinMode(BUZZER_PIN, OUTPUT);
  pinMode(PIR_PIN, INPUT);

  digitalWrite(GREEN_LED, LOW);
  digitalWrite(RED_LED, LOW);
  digitalWrite(BUZZER_PIN, LOW);

  SPI.begin();
  rfid.PCD_Init();

  kapi.attach(SERVO_PIN);
  kapi.write(0);

  lcd.init();
  lcd.backlight();
  lcd.setCursor(0, 0);
  lcd.print("Sistem Hazir");
  lcd.setCursor(0, 1);
  lcd.print("Kart Okutunuz");

  digitalWrite(GREEN_LED, HIGH);
  digitalWrite(RED_LED, HIGH);
  delay(500);
  digitalWrite(GREEN_LED, LOW);
  digitalWrite(RED_LED, LOW);

  Serial.println("SISTEM_HAZIR");
}

void loop() {
  if (digitalRead(PIR_PIN) == HIGH && !kapiAcik) {
    if (millis() - sonHareketZamani > HAREKET_BEKLEME) {
      Serial.println("HAREKET_ALGILANDI");
      sonHareketZamani = millis();
    }
  }

  if (kapiAcik && (millis() - kapiAcilmaZamani > KAPI_BEKLEME)) {
    kapiKapat();
  }

  if (!rfid.PICC_IsNewCardPresent()) return;
  if (!rfid.PICC_ReadCardSerial()) return;

  String uid = "";
  for (byte i = 0; i < rfid.uid.size; i++) {
    if (rfid.uid.uidByte[i] < 0x10) uid += "0";
    uid += String(rfid.uid.uidByte[i], HEX);
    if (i < rfid.uid.size - 1) uid += " ";
  }
  uid.toUpperCase();

  Serial.print("KART_OKUNDU:");
  Serial.println(uid);

  if (kartYetkiliMi(uid)) {
    yetkiliGiris(uid);
  } else {
    yetkisizGiris(uid);
  }

  rfid.PICC_HaltA();
  rfid.PCD_StopCrypto1();
}

bool kartYetkiliMi(String uid) {
  for (int i = 0; i < kartSayisi; i++) {
    if (yetkiliKartlar[i] == uid) return true;
  }
  return false;
}

void kapiAc() {
  kapi.write(90);
  kapiAcik = true;
  kapiAcilmaZamani = millis();
  digitalWrite(GREEN_LED, HIGH);
  digitalWrite(BUZZER_PIN, HIGH);
  delay(100);
  digitalWrite(BUZZER_PIN, LOW);
  Serial.println("KAPI_ACILDI");
}

void kapiKapat() {
  kapi.write(0);
  kapiAcik = false;
  digitalWrite(GREEN_LED, LOW);
  Serial.println("KAPI_KAPANDI");
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("Sistem Hazir");
  lcd.setCursor(0, 1);
  lcd.print("Kart Okutunuz");
}

void yetkiliGiris(String uid) {
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("Hos Geldiniz!");
  lcd.setCursor(0, 1);
  lcd.print(uid);
  kapiAc();
  Serial.print("{\"durum\":\"yetkili\",\"uid\":\"");
  Serial.print(uid);
  Serial.println("\",\"kaynak\":\"rfid\"}");
}

void yetkisizGiris(String uid) {
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("Yetkisiz Giris!");
  lcd.setCursor(0, 1);
  lcd.print(uid);
  digitalWrite(RED_LED, HIGH);
  for (int i = 0; i < 3; i++) {
    digitalWrite(BUZZER_PIN, HIGH);
    delay(200);
    digitalWrite(BUZZER_PIN, LOW);
    delay(200);
  }
  digitalWrite(RED_LED, LOW);
  Serial.print("{\"durum\":\"yetkisiz\",\"uid\":\"");
  Serial.print(uid);
  Serial.println("\",\"kaynak\":\"rfid\"}");
  delay(1000);
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("Sistem Hazir");
  lcd.setCursor(0, 1);
  lcd.print("Kart Okutunuz");
}
