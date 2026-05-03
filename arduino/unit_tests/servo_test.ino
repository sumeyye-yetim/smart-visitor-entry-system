cpp
#include <Servo.h>
Servo kapi;
void setup() {
  kapi.attach(6);
  kapi.write(0);
  delay(1000);
}
void loop() {
  kapi.write(90);
  delay(2000);
  kapi.write(0);
  delay(2000);
}
