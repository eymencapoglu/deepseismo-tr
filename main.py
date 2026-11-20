# ==============================================================================
# DEEPSEISMO-TR: SİSMİK YAPAY ZEKA SİSTEMİ (LOCAL VERSION - SSL FIX)
# ==============================================================================

# --- 1. SSL/SERTİFİKA HATASI İÇİN "NÜKLEER" ÇÖZÜM ---
# Requests kütüphanesinin varsayılan davranışını değiştiriyoruz.
import requests
import urllib3
import functools

# Uyarıları kapat
urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

# Orijinal request fonksiyonunu sakla
old_request = requests.Session.request

# Yeni ve "güvenliği umursamayan" fonksiyon
def new_request(self, method, url, *args, **kwargs):
    kwargs['verify'] = False # Asla sertifika sorma
    return old_request(self, method, url, *args, **kwargs)

# Kütüphaneyi yamala (Monkey Patch)
requests.Session.request = new_request
# -------------------------------------------------------

import os
import time
import numpy as np
import torch
import xgboost as xgb
import seisbench.models as sbm
from colorama import init, Fore, Style

init()

print(Fore.CYAN + "==========================================")
print("   🚀 DEEPSEISMO-TR: SİSTEM BAŞLATILIYOR...   ")
print("==========================================" + Style.RESET_ALL)

# --- 2. AYARLAR VE KONTROLLER ---
MODEL_FILE = "DeepSeismo_TR_Final.json"

if not os.path.exists(MODEL_FILE):
    print(Fore.RED + f"\n[HATA] '{MODEL_FILE}' dosyası bulunamadı!" + Style.RESET_ALL)
    print("Lütfen indirdiğin .json dosyasını bu script ile aynı klasöre koy.")
    exit()

# --- 3. MODELLERİ YÜKLEME ---
print(Fore.YELLOW + "\n[1/2] Karar Mekanizması (XGBoost) yükleniyor..." + Style.RESET_ALL)
try:
    learner = xgb.XGBClassifier()
    learner.load_model(MODEL_FILE)
    print(Fore.GREEN + "   ✅ Başarılı: Model Hafızası Yüklendi." + Style.RESET_ALL)
except Exception as e:
    print(Fore.RED + f"   ❌ Hata: Model yüklenemedi. {e}" + Style.RESET_ALL)
    exit()

print(Fore.YELLOW + "[2/2] Derin Öğrenme Motoru (EQTransformer) hazırlanıyor..." + Style.RESET_ALL)
print("      (İlk çalıştırışta model internetten indirilecek, lütfen bekleyin...)")

try:
    # Artık SSL hatası vermesi imkansız çünkü verify=False yaptık
    eqt = sbm.EQTransformer.from_pretrained("stead")
    eqt.eval()
    print(Fore.GREEN + "   ✅ Başarılı: Gözler açıldı." + Style.RESET_ALL)
except Exception as e:
    print(Fore.RED + f"\n   ❌ İndirme Hatası: {e}" + Style.RESET_ALL)
    print("   Eğer hala hata alıyorsan, 'Manual İndirme' yöntemine geçeceğiz.")
    exit()

# --- 4. ÖZELLİK ÇIKARMA (BEYİN OKUMA) ---
activation = {}
def get_activation(name):
    def hook(model, input, output):
        activation[name] = output.detach()
    return hook

eqt.encoder.register_forward_hook(get_activation('encoder_output'))

# --- 5. ANALİZ FONKSİYONU ---
def analiz_et(sinyal_verisi):
    tensor = torch.from_numpy(sinyal_verisi).float().unsqueeze(0).unsqueeze(0).repeat(1, 3, 1)
    with torch.no_grad():
        _ = eqt(tensor)
    features = np.max(activation['encoder_output'].cpu().numpy(), axis=2).reshape(1, -1)
    tahmin = learner.predict(features)
    olasilik = learner.predict_proba(features)[0][1]
    return tahmin[0], olasilik

# --- 6. ANA SİMÜLASYON DÖNGÜSÜ ---
print(Fore.CYAN + "\n------------------------------------------")
print("   SİSTEM AKTİF! CANLI İZLEME MODU (SİMÜLASYON)   ")
print("------------------------------------------" + Style.RESET_ALL)
print("Çıkmak için Ctrl+C tuşlarına basabilirsin.\n")

try:
    while True:
        sismik_veri = np.random.normal(0, 1, 6000)
        
        # Rastgele Deprem Enjekte Et (%20 İhtimalle)
        if np.random.rand() > 0.8:
            x = np.linspace(0, 10, 1000)
            dalga = np.sin(x) * 10 * np.exp(-x/2)
            sismik_veri[2000:3000] += dalga
            sismik_veri[2000:3000] += np.random.normal(0, 2, 1000)
            print(Fore.MAGENTA + f"📡 [SİMÜLASYON] İstasyona SİSMİK DALGA gönderiliyor..." + Style.RESET_ALL)

        karar, guven_skoru = analiz_et(sismik_veri)
        
        if karar == 1:
            print(Fore.RED + Style.BRIGHT + f"🚨 [ALARM] DEPREM TESPİT EDİLDİ! (Güven: %{guven_skoru*100:.2f})" + Style.RESET_ALL)
        else:
            print(Fore.BLUE + f"✅ [DURUM] Ortam Sakin. (Temiz Sinyal: %{(1-guven_skoru)*100:.2f})" + Style.RESET_ALL)
            
        time.sleep(2)

except KeyboardInterrupt:
    print(Fore.YELLOW + "\n🛑 Kullanıcı komutuyla sistem kapatıldı." + Style.RESET_ALL)