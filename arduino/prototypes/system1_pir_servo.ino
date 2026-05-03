
#include <Servo.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>

#define SERVO_PIN   6
#define PIR_PIN     7
#define GREEN_LED   4
#define RED_LED     5

Servo kapi;
LiquidCrystal_I2C lcd(0x27, 16, 2);

bool kapiAcik = false;
unsigned long kapiAcilmaZamani = 0;
unsigned long sonHareketZamani = 0;
const int KAPI_BEKLEME = 3000;
const int HAREKET_BEKLEME = 5000;

void setup() {
  Serial.begin(9600);
  pinMode(GREEN_LED, OUTPUT);
  pinMode(RED_LED, OUTPUT);
  pinMode(PIR_PIN, INPUT);

  digitalWrite(GREEN_LED, LOW);
  digitalWrite(RED_LED, LOW);

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
}

void kapiAc() {
  kapi.write(90);
  kapiAcik = true;
  kapiAcilmaZamani = millis();
  digitalWrite(GREEN_LED, HIGH);
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
}

void yetkisizGiris(String uid) {
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("Yetkisiz Giris!");
  lcd.setCursor(0, 1);
  lcd.print(uid);
  digitalWrite(RED_LED, HIGH);
  delay(2000);
  digitalWrite(RED_LED, LOW);
  delay(1000);
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("Sistem Hazir");
  lcd.setCursor(0, 1);
  lcd.print("Kart Okutunuz");
}
