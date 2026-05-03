cpp
#define PIR_PIN 7
void setup() {
  Serial.begin(9600);
  pinMode(PIR_PIN, INPUT);
  Serial.println("PIR hazir...");
  delay(2000);
}
void loop() {
  if (digitalRead(PIR_PIN) == HIGH) {
    Serial.println("HAREKET ALGILANDI!");
  } else {
    Serial.println("Hareket yok.");
  }
  delay(500);
}
