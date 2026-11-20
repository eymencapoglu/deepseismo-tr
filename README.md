# DeepSeismo-TR — AI-Powered Real-Time Seismic Monitoring System

*[English](#english) | [Türkçe](#türkçe)*

---

<a name="english"></a>
## English

### Overview
**DeepSeismo-TR** is a hybrid deep-learning and gradient-boosting seismic monitoring prototype built to distinguish real earthquake waveforms from background noise in near real time. It extracts deep waveform representations from a pretrained **EQTransformer** encoder (via SeisBench, trained on Stanford's STEAD dataset) and feeds them into a lightweight **XGBoost** binary classifier (`DeepSeismo_TR_Final.json`).

The project includes three interfaces:
1. **`main.py`** — Terminal-based live seismic stream simulator and real-time alarm engine.
2. **`monitor.py`** — Interactive Matplotlib live seismograph displaying the Z-component waveform, live earthquake probability curve, threshold line, and a manual *"Trigger Earthquake Test"* button.
3. **`deepseismo.php`** — Futuristic bilingual (TR/EN) real-time web monitoring dashboard that consumes a `/live` JSON endpoint.

### Development Timeline & Process
- **February 11, 2026:** Trained and exported the XGBoost decision layer (`DeepSeismo_TR_Final.json`) on top of EQTransformer encoder embeddings, and built both the CLI engine (`main.py`) and live Matplotlib monitor (`monitor.py`).
- **February 27, 2026:** Designed and built the real-time web dashboard (`deepseismo.php`) with canvas seismograph animations and live `/live` API integration.
- **Security Note:** The personal temporary ngrok tunnel URL originally hardcoded in `deepseismo.php` has been removed and replaced with the `DEEPSEISMO_API_URL` environment variable (see `.env.example`).

### How It Works
1. A 60-second (6,000-sample @ 100 Hz) seismic window is passed into `EQTransformer` (`from_pretrained("stead")`).
2. A PyTorch forward hook captures the latent `encoder_output` tensor and applies global max pooling across the time dimension.
3. The resulting 64-dimensional feature vector is classified by `DeepSeismo_TR_Final.json` (XGBoost) to produce an earthquake probability score; scores above `0.80` trigger an alarm.

### Setup & Usage
```bash
pip install -r requirements.txt

# 1. Terminal simulation mode
python main.py

# 2. Live Matplotlib seismograph GUI
python monitor.py

# 3. Web dashboard (configure DEEPSEISMO_API_URL first)
export DEEPSEISMO_API_URL="http://localhost:8000/live"
php -S localhost:8080
```

### Acknowledgments & Open-Source Credits
We sincerely thank the researchers and open-source maintainers whose work made DeepSeismo-TR possible:
- **[SeisBench](https://github.com/seisbench/seisbench)** (Woollam et al., *Seismological Research Letters*, 2022) — Open-source seismic machine learning toolbox.
- **[EQTransformer](https://github.com/smousavi05/EQTransformer)** (Mousavi et al., *Nature Communications*, 2020) — Attentive deep learning model for simultaneous earthquake detection and phase picking.
- **[STEAD (Stanford Earthquake Dataset)](https://github.com/smousavi05/STEAD)** (Mousavi et al., *IEEE Access*, 2019) — Global seismic waveform dataset used to pretrain the EQTransformer backbone.
- **[XGBoost](https://github.com/dmlc/xgboost)**, **[PyTorch](https://pytorch.org/)**, and **[Matplotlib](https://matplotlib.org/)**.

---

<a name="türkçe"></a>
## Türkçe

### Genel Bakış
**DeepSeismo-TR**, sismik sinyalleri gerçek zamanlı olarak analiz edip gerçek deprem dalgalarını ortam gürültüsünden ayırt etmek üzere geliştirilmiş hibrit bir yapay zeka prototipidir. Derin öğrenme tarafında **SeisBench** üzerinden **EQTransformer** (Stanford STEAD veri setiyle önceden eğitilmiş) modelinin encoder katmanından öznitelik çıkarır, karar katmanında ise eğitilmiş hafif bir **XGBoost** sınıflandırıcısı (`DeepSeismo_TR_Final.json`) kullanır.

Proje üç farklı kullanım katmanı sunar:
1. **`main.py`** — Terminal üzerinden canlı simülasyon ve gerçek zamanlı deprem alarm akışı.
2. **`monitor.py`** — Z-bileşeni sismik dalgasını, anlık güven skorunu ve eşik çizgisini gösteren, *"Deprem Testi Yap"* butonlu canlı Matplotlib takip arayüzü.
3. **`deepseismo.php`** — `/live` JSON uç noktasından canlı veri çekebilen, Türkçe/İngilizce destekli modern web izleme paneli.

### Geliştirme Süreci ve Tarihçesi
- **11 Şubat 2026:** EQTransformer encoder çıktıları üzerine XGBoost karar modeli (`DeepSeismo_TR_Final.json`) eğitildi; terminal simülasyonu (`main.py`) ve canlı grafik ekranı (`monitor.py`) geliştirildi.
- **27 Şubat 2026:** Canlı sismograf animasyonlu web takip arayüzü (`deepseismo.php`) geliştirildi.
- **Güvenlik Notu:** Geliştirme sırasında `deepseismo.php` içinde kullanılan kişisel ngrok adresi temizlenmiş, yerine `DEEPSEISMO_API_URL` ortam değişkeni eklenmiştir (`.env.example` dosyasına bakınız).

### Kurulum ve Çalıştırma
```bash
pip install -r requirements.txt

# Terminal simülasyonu
python main.py

# Canlı Matplotlib grafik arayüzü
python monitor.py

# Web paneli
export DEEPSEISMO_API_URL="http://localhost:8000/live"
php -S localhost:8080
```

### Açık Kaynak Teşekkürler ve Kaynaklar
Bu projenin temelini oluşturan açık kaynak topluluklara ve araştırmacılara teşekkür ederiz:
- **[SeisBench](https://github.com/seisbench/seisbench)** (Woollam vd., 2022) — Açık kaynak sismik makine öğrenmesi kütüphanesi.
- **[EQTransformer](https://github.com/smousavi05/EQTransformer)** (Mousavi vd., *Nature Communications*, 2020) — Deprem tespiti ve faz belirleme derin öğrenme mimarisi.
- **[STEAD (Stanford Earthquake Dataset)](https://github.com/smousavi05/STEAD)** (Mousavi vd., 2019) — EQTransformer omurgasının ön eğitiminde kullanılan küresel deprem veri seti.
- **[XGBoost](https://github.com/dmlc/xgboost)**, **[PyTorch](https://pytorch.org/)** ve **[Matplotlib](https://matplotlib.org/)**.