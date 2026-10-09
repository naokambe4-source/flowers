# Derin Flowers — Bekleyen talepler (not)

Bu dosya müşterinin istediği ama henüz yapılmamış işlerin notudur. Yapılan iş buradan silinir.

## 1. Yapay zekâ ile ürün içeriği (temaya entegre, ayrı eklenti değil)
- Ürün ekranında başlık yazılınca "Yapay zekâ ile içerik oluştur" düğmesi.
- Üretilecekler: SEO uyumlu ürün başlığı önerisi, kısa açıklama, uzun açıklama, meta başlık (~60 karakter),
  meta açıklama (~155 karakter), görsel ALT metni, etiket önerisi.
- Hiçbir şey kendiliğinden yayınlanmaz: taslak alanlara dolar, yönetici okuyup onaylar.
- Meta başlık / açıklama Rank Math alanlarına yazılır (tek SEO eklentisi Rank Math).
- Sağlayıcı seçimi panelde: Gemini veya ChatGPT (OpenAI); Claude da seçenek olarak kalabilir. Anahtarı müşteri girer.
- Başlık havuzu ve AYZ0001 ürün kodu ile birlikte çalışır.
- Eski "Derin AI Ürün" eklentisi bu temayla birlikte kurulmamalı (AYZ kodları çakışır).

## 2. Sipariş fişi / kart baskı düzeni (müşterinin gönderdiği örnek fotoğrafa göre)
- Kâğıt: hazır delikli (perforeli) A4. Örnek fişte sol taraf kart notu bölümü; delikli çizgiden koparılıyor.
- Sol bölüm (koparılan kart): logo, kart notu (italik, ortalı), altında imza (kalın, büyük harf).
- Sağ bölüm: ürün görseli + tablo:
  Teslimat Türü, Sipariş Adedi, Sipariş No, Teslimat Tarihi/Saati, Ürünler (ürün kodu - ürün adı),
  Gönderen (ad / telefon), Alıcının Adı/Numarası, Alıcının Adresi (adres + tarif + ilçe).
- Ek ürün (hediye: ayıcık, çikolata, pasta…) alındıysa fişin EN ÜSTÜNDE belirgin şekilde yazılır.
- Ölçü: müşteri "1 A4'ten dört tane koparıyoruz" ve "A4 üçe bölünüyor" dedi.
  Kesin ölçü (A4 başına 3 mü 4 mü fiş, delik çizgisinin soldan mesafesi) müşteriden teyit edilecek;
  baskı ölçüleri buna göre milimetrik ayarlanacak.

## 3. Teslimden sonra değerlendirme mesajı (WhatsApp + e-posta, ikisi birden)
- Sipariş "Teslim edildi" yapılınca müşteriye mesaj + bağlantı:
  "Aldığınız hizmeti değerlendirir misiniz?" (bağlantı: Google yorum / değerlendirme sayfası).
- E-posta: mevcut (v1.10'da tamamlandı e-postasına eklendi) — korunacak.
- WhatsApp: otomatik gönderim için WasenderAPI (https://wasenderapi.com/api-docs) bağlanacak;
  API anahtarı panelden girilecek. Şu an sadece elle "WhatsApp ile gönder" düğmesi var.
