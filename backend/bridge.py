import serial
import requests
import json
import time

# Arduino'nun bağlı olduğu port
SERIAL_PORT = '/dev/cu.usbserial-10'
BAUD_RATE = 9600

# PHP API adresi
API_URL = 'http://localhost/apartment-visitor-system/rfid_api.php'

print("Bridge başlatılıyor...")

try:
    ser = serial.Serial(SERIAL_PORT, BAUD_RATE, timeout=1)
    print(f"Arduino bağlandı: {SERIAL_PORT}")
    time.sleep(2)
except Exception as e:
    print(f"Port hatası: {e}")
    exit()

print("Dinleniyor...")

while True:
    try:
        line = ser.readline().decode('utf-8').strip()
        
        if not line:
            continue
            
        print(f"Arduino: {line}")
        
        # JSON verisi gelince API'ye gönder
        if line.startswith('{'):
            try:
                data = json.loads(line)
                response = requests.post(
                    API_URL,
                    json=data,
                    headers={'Content-Type': 'application/json'}
                )
                result = response.json()
                print(f"API yanıtı: {result}")
            except json.JSONDecodeError:
                print(f"JSON hatası: {line}")
            except requests.exceptions.RequestException as e:
                print(f"API hatası: {e}")
                
    except KeyboardInterrupt:
        print("Bridge durduruldu.")
        ser.close()
        break
    except Exception as e:
        print(f"Hata: {e}")
