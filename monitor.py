import os
import time
import numpy as np
import torch
import xgboost as xgb
import seisbench.models as sbm
import matplotlib.pyplot as plt
import matplotlib.animation as animation
from matplotlib.widgets import Button
import requests
import urllib3

# --- 1. SSL HATASI İÇİN DÜZELTİLMİŞ YAMA (RECURSION FIX) ---
urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

# Orijinal fonksiyonu SAKLIYORUZ (Kritik nokta burası)
original_request = requests.Session.request

def patched_request(self, method, url, *args, **kwargs):
    kwargs['verify'] = False # Sertifika sorma
    return original_request(self, method, url, *args, **kwargs)

# Yamayı uyguluyoruz
requests.Session.request = patched_request
# -----------------------------------------------------------

# --- AYARLAR ---
MODEL_FILE = "DeepSeismo_TR_Final.json"
CHUNK_SIZE = 6000 # 1 dakikalık pencere

print("Görsel arayüz yükleniyor... (Model indirilirken pencere donabilir, bekle)")

# 1. XGBoost Yükle
learner = xgb.XGBClassifier()
if os.path.exists(MODEL_FILE):
    learner.load_model(MODEL_FILE)
    print("✅ Model hafızası yüklendi.")
else:
    print(f"HATA: {MODEL_FILE} bulunamadı! Lütfen aynı klasörde olduğundan emin ol.")
    # Demo için devam et (Hata verip kapanmasın)
    # exit()

# 2. EQTransformer Yükle
try:
    eqt = sbm.EQTransformer.from_pretrained("stead")
    eqt.eval()
    print("✅ Yapay zeka motoru hazır.")
except Exception as e:
    print(f"❌ Model indirme hatası: {e}")
    print("Manuel indirme gerekebilir.")
    exit()

# Kanca (Hook)
activation = {}
def get_activation(name):
    def hook(model, input, output):
        activation[name] = output.detach()
    return hook
eqt.encoder.register_forward_hook(get_activation('encoder_output'))

# --- ANALİZ FONKSİYONU ---
def get_prediction(signal):
    tensor = torch.from_numpy(signal).float().unsqueeze(0).unsqueeze(0).repeat(1, 3, 1)
    with torch.no_grad():
        _ = eqt(tensor)
    features = np.max(activation['encoder_output'].cpu().numpy(), axis=2).reshape(1, -1)
    # Model yüklü değilse rastgele değer döndür (Demo modu)
    if hasattr(learner, 'predict_proba'):
        prob = learner.predict_proba(features)[0][1]
    else:
        prob = 0.0
    return prob

# --- GÖRSELLEŞTİRME ---
fig, (ax1, ax2) = plt.subplots(2, 1, figsize=(10, 6), sharex=True)
plt.subplots_adjust(bottom=0.2)
fig.canvas.manager.set_window_title('DeepSeismo-TR: Canlı Takip Ekranı')

# Veri tamponları
x_data = np.arange(CHUNK_SIZE)
y_data = np.zeros(CHUNK_SIZE)
prob_data = np.zeros(CHUNK_SIZE)

# Çizgiler
line1, = ax1.plot(x_data, y_data, color='black', lw=0.8)
line2, = ax2.plot(x_data, prob_data, color='blue', lw=2)

# Eşik
ax2.axhline(y=0.8, color='red', linestyle='--', alpha=0.5, label='Alarm')

# Eksenler
ax1.set_title('Canlı Sismik Sinyal (Z Bileşeni)', fontsize=10, fontweight='bold')
ax1.set_ylabel('Genlik')
ax1.set_ylim(-10, 10)
ax1.grid(True, alpha=0.3)

ax2.set_title('DeepSeismo Güven Skoru', fontsize=10, fontweight='bold')
ax2.set_ylabel('Olasılık')
ax2.set_ylim(0, 1.1)
ax2.grid(True, alpha=0.3)

# Durum Yazısı
status_text = ax1.text(0.02, 0.9, "DURUM: SAKİN", transform=ax1.transAxes, 
                       fontsize=12, fontweight='bold', color='green',
                       bbox=dict(facecolor='white', alpha=0.8))

# --- CANLI AKIŞ ---
inject_quake = False

def update(frame):
    global y_data, prob_data, inject_quake
    
    # 1. Veri Kaydır
    step = 100 # Hız
    y_data = np.roll(y_data, -step)
    prob_data = np.roll(prob_data, -step)
    
    # 2. Gürültü Üret
    new_noise = np.random.normal(0, 0.5, step)
    
    # 3. Deprem Enjeksiyonu
    if inject_quake:
        t = np.linspace(0, np.pi, step)
        # Sönümlenen sinüs dalgası
        quake = np.sin(t*8) * 8 * np.random.uniform(0.8, 1.2)
        new_noise += quake
        # Deprem bitti mi?
        if np.random.rand() > 0.92: inject_quake = False

    y_data[-step:] = new_noise
    
    # 4. Analiz
    prob = get_prediction(y_data)
    prob_data[-step:] = prob
    
    # 5. Grafik Güncelle
    line1.set_ydata(y_data)
    line2.set_ydata(prob_data)
    
    # Alarm Rengi
    if prob > 0.8:
        status_text.set_text(f"🚨 ALARM! ({prob:.2f})")
        status_text.set_color('red')
        ax1.set_facecolor('#ffe6e6')
    else:
        status_text.set_text(f"✅ SAKİN ({prob:.2f})")
        status_text.set_color('green')
        ax1.set_facecolor('white')

    return line1, line2, status_text

# Buton
def trigger(event):
    global inject_quake
    inject_quake = True
    print("⚠️ Sismik dalga gönderiliyor...")

ax_btn = plt.axes([0.4, 0.05, 0.2, 0.075])
btn = Button(ax_btn, 'Deprem Testi Yap', color='orange', hovercolor='red')
btn.on_clicked(trigger)

ani = animation.FuncAnimation(fig, update, interval=50, blit=False)
plt.show()