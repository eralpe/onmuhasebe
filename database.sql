-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1:3306
-- Üretim Zamanı: 01 Eyl 2025, 12:24:27
-- Sunucu sürümü: 8.2.0
-- PHP Sürümü: 8.2.13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `on_muhasebe`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `faturalar`
--

DROP TABLE IF EXISTS `faturalar`;
CREATE TABLE IF NOT EXISTS `faturalar` (
  `id` int NOT NULL AUTO_INCREMENT,
  `toptanci_id` int NOT NULL,
  `fatura_no` varchar(50) NOT NULL,
  `tutar` decimal(10,2) NOT NULL,
  `tarih` date NOT NULL,
  `aciklama` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `toptanci_id` (`toptanci_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `fatura_kalemleri`
--

DROP TABLE IF EXISTS `fatura_kalemleri`;
CREATE TABLE IF NOT EXISTS `fatura_kalemleri` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fatura_id` int NOT NULL,
  `urun_adi` varchar(255) NOT NULL,
  `adet` int NOT NULL,
  `birim_fiyat` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fatura_id` (`fatura_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `gelir_gider`
--

DROP TABLE IF EXISTS `gelir_gider`;
CREATE TABLE IF NOT EXISTS `gelir_gider` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tur` enum('gelir','gider') NOT NULL,
  `aciklama` varchar(255) NOT NULL,
  `tutar` decimal(10,2) NOT NULL,
  `tarih` date NOT NULL,
  `kategori_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `kategori_id` (`kategori_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `gunluk_satis`
--

DROP TABLE IF EXISTS `gunluk_satis`;
CREATE TABLE IF NOT EXISTS `gunluk_satis` (
  `id` int NOT NULL AUTO_INCREMENT,
  `urun` varchar(255) NOT NULL,
  `adet` int NOT NULL,
  `tutar` decimal(10,2) NOT NULL,
  `tarih` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kasa`
--

DROP TABLE IF EXISTS `kasa`;
CREATE TABLE IF NOT EXISTS `kasa` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hareket` enum('giris','cikis') NOT NULL,
  `aciklama` varchar(255) NOT NULL,
  `tutar` decimal(10,2) NOT NULL,
  `tarih` date NOT NULL,
  `kasa_id` int DEFAULT NULL,
  `para_birimi` varchar(3) DEFAULT 'TL',
  PRIMARY KEY (`id`),
  KEY `kasa_id` (`kasa_id`),
  KEY `para_birimi` (`para_birimi`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kasalar`
--

DROP TABLE IF EXISTS `kasalar`;
CREATE TABLE IF NOT EXISTS `kasalar` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ad` varchar(100) NOT NULL,
  `aciklama` varchar(255) DEFAULT NULL,
  `kategori` enum('Nakit','Banka','Dijital') DEFAULT 'Nakit',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `kasalar`
--

INSERT INTO `kasalar` (`id`, `ad`, `aciklama`, `kategori`) VALUES
(1, 'Merkez Kasa', 'Ana kasa', 'Nakit');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kasa_transferleri`
--

DROP TABLE IF EXISTS `kasa_transferleri`;
CREATE TABLE IF NOT EXISTS `kasa_transferleri` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kaynak_kasa_id` int NOT NULL,
  `hedef_kasa_id` int NOT NULL,
  `tutar` decimal(10,2) NOT NULL,
  `tarih` date NOT NULL,
  `aciklama` varchar(255) DEFAULT NULL,
  `kaynak_kasa_hareket_id` int DEFAULT NULL,
  `hedef_kasa_hareket_id` int DEFAULT NULL,
  `para_birimi` varchar(3) DEFAULT 'TL',
  PRIMARY KEY (`id`),
  KEY `kaynak_kasa_id` (`kaynak_kasa_id`),
  KEY `hedef_kasa_id` (`hedef_kasa_id`),
  KEY `kaynak_kasa_hareket_id` (`kaynak_kasa_hareket_id`),
  KEY `hedef_kasa_hareket_id` (`hedef_kasa_hareket_id`),
  KEY `para_birimi` (`para_birimi`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kategoriler`
--

DROP TABLE IF EXISTS `kategoriler`;
CREATE TABLE IF NOT EXISTS `kategoriler` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ad` varchar(100) NOT NULL,
  `tur` enum('gelir','gider') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `kategoriler`
--

INSERT INTO `kategoriler` (`id`, `ad`, `tur`) VALUES
(1, 'Satış Geliri', 'gelir'),
(2, 'Diğer Gelir', 'gelir'),
(3, 'Kira Gideri', 'gider'),
(4, 'Personel Maaşları', 'gider'),
(5, 'Mal Alımı', 'gider'),
(6, 'Diğer Giderler', 'gider');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kullanicilar`
--

DROP TABLE IF EXISTS `kullanicilar`;
CREATE TABLE IF NOT EXISTS `kullanicilar` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kullanici_adi` varchar(50) NOT NULL,
  `sifre` varchar(255) NOT NULL,
  `rol` enum('admin','user') DEFAULT 'user',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `kullanicilar`
--

INSERT INTO `kullanicilar` (`id`, `kullanici_adi`, `sifre`, `rol`) VALUES
(1, 'admin', '$2y$10$X2pzvI8A6xpcQBKG20A.C.aqDE8SLe.YR89O3xRFNDjuMbwfNBovu', 'admin'),
(2, 'testuser', '$2y$10$K5y9V8s3z4x6Qw1p2m7nO.9rX8tY0v2jL3k4h5g6i7j8k9l0m1n2o', 'user');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `para_birimleri`
--

DROP TABLE IF EXISTS `para_birimleri`;
CREATE TABLE IF NOT EXISTS `para_birimleri` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kod` varchar(3) NOT NULL,
  `ad` varchar(50) NOT NULL,
  `tl_kuru` decimal(10,4) DEFAULT '1.0000',
  PRIMARY KEY (`id`),
  UNIQUE KEY `kod` (`kod`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `para_birimleri`
--

INSERT INTO `para_birimleri` (`id`, `kod`, `ad`, `tl_kuru`) VALUES
(1, 'TL', 'Türk Lirası', 1.0000),
(2, 'USD', 'Amerikan Doları', 34.0000),
(3, 'EUR', 'Euro', 36.0000);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `personel`
--

DROP TABLE IF EXISTS `personel`;
CREATE TABLE IF NOT EXISTS `personel` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ad_soyad` varchar(100) NOT NULL,
  `pozisyon` varchar(100) DEFAULT NULL,
  `maas` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `personel`
--

INSERT INTO `personel` (`id`, `ad_soyad`, `pozisyon`, `maas`) VALUES
(1, 'İsmail Sevinç', 'Garson', 28000.00);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `personel_odemeler`
--

DROP TABLE IF EXISTS `personel_odemeler`;
CREATE TABLE IF NOT EXISTS `personel_odemeler` (
  `id` int NOT NULL AUTO_INCREMENT,
  `personel_id` int NOT NULL,
  `tutar` decimal(10,2) NOT NULL,
  `tarih` date NOT NULL,
  `aciklama` varchar(255) DEFAULT NULL,
  `gelir_gider_id` int DEFAULT NULL,
  `kasa_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `personel_id` (`personel_id`),
  KEY `gelir_gider_id` (`gelir_gider_id`),
  KEY `kasa_id` (`kasa_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `stok`
--

DROP TABLE IF EXISTS `stok`;
CREATE TABLE IF NOT EXISTS `stok` (
  `id` int NOT NULL AUTO_INCREMENT,
  `urun_adi` varchar(255) NOT NULL,
  `adet` int NOT NULL DEFAULT '0',
  `birim_fiyat` decimal(10,2) NOT NULL,
  `alim_fiyat` decimal(10,2) NOT NULL DEFAULT '0.00',
  `satis_fiyat` decimal(10,2) NOT NULL DEFAULT '0.00',
  `toptanci_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `toptanci_id` (`toptanci_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `toptancilar`
--

DROP TABLE IF EXISTS `toptancilar`;
CREATE TABLE IF NOT EXISTS `toptancilar` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ad` varchar(100) NOT NULL,
  `adres` varchar(255) DEFAULT NULL,
  `telefon` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `toptancilar`
--

INSERT INTO `toptancilar` (`id`, `ad`, `adres`, `telefon`, `email`) VALUES
(1, 'REİS GIDA', '', '', '');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `toptanci_odemeler`
--

DROP TABLE IF EXISTS `toptanci_odemeler`;
CREATE TABLE IF NOT EXISTS `toptanci_odemeler` (
  `id` int NOT NULL AUTO_INCREMENT,
  `toptanci_id` int NOT NULL,
  `tutar` decimal(10,2) NOT NULL,
  `tarih` date NOT NULL,
  `aciklama` varchar(255) DEFAULT NULL,
  `gelir_gider_id` int DEFAULT NULL,
  `kasa_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `toptanci_id` (`toptanci_id`),
  KEY `gelir_gider_id` (`gelir_gider_id`),
  KEY `kasa_id` (`kasa_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
