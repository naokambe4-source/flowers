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

## 4. Ürün etiketleri otomatik
- Ürün kaydedilirken etiketler otomatik atansın (yapay zekâ önerisiyle ya da ürün adı, kategori, renk ve çiçek
  türünden). Örnek: "kırmızı gül", "doğum günü", "kutuda çiçek". Yönetici sonradan silip ekleyebilir.
- Madde 1'deki yapay zekâ içeriğiyle birlikte yapılacak.

## 5. Ürün detayında teslimat / tarih seçimi — yeni tasarım (müşterinin gönderdiği görsele göre)
- Başlık "Teslimat bilgileri" + alt yazı "Çiçeklerinizin ne zaman ve nasıl ulaşacağını seçin."
- Teslimat türü: iki büyük kart (ikon + başlık + alt yazı + sağda yuvarlak seçim işareti):
  "Adrese teslim — İzmir'in seçili bölgelerine" / "Mağazadan teslim — Alsancak Atölye". Seçili kart yeşil çerçeve + açık yeşil zemin.
- Teslimat bölgesi: konum ikonlu tek açılır liste ("Karşıyaka · Bostanlı" gibi).
- Teslimat tarihi: üç kart — "Bugün (8 Ekim)", "Yarın (9 Ekim)", "İleri tarih seç (Takvimden seçin)".
  Takvim yalnızca "İleri tarih seç"e basılınca açılır (şu an takvim hep açık ve uzun).
- Teslimat saati: saat aralıkları saat ikonlu kartlar halinde (09:00–13:00, 14:00–18:00), sağda seçim işareti.
- Altında bilgi satırı: "Aynı gün veya ileri tarihli teslimat seçebilirsiniz."
- Tam genişlik yeşil "Devam et →" düğmesi.
- Renkler: krem zemin, koyu yeşil vurgu, başlıklar serif. Sol tarafta ürün görseli + fiyat/adet/toplam özeti.
