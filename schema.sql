-- ============================================================
-- DDL: tr_customer_satisfaction_waschen
-- PT Waschen Alora Indonesia
-- Dibuat: 2026-04-28
-- ============================================================

CREATE TABLE IF NOT EXISTS `tr_customer_satisfaction_waschen` (
  `id`            BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT       COMMENT 'Primary key',
  `no_nota`       VARCHAR(100)     NOT NULL                      COMMENT 'Nomor nota transaksi pelanggan',
  `csat_score`    TINYINT UNSIGNED NOT NULL                      COMMENT 'Skor kepuasan 1–5 (1=Sangat Tidak Puas … 5=Sangat Puas)',
  `csat_label`    VARCHAR(50)      NOT NULL                      COMMENT 'Label deskriptif skor CSAT',
  `nps_score`     TINYINT UNSIGNED NOT NULL                      COMMENT 'Skor NPS 0–10 (0=Tidak Mungkin … 10=Sangat Mungkin)',
  `nps_category`  ENUM('Detractor','Passive','Promoter')
                                   NOT NULL                      COMMENT 'Detractor(0-6) | Passive(7-8) | Promoter(9-10)',
  `feedback_tags` VARCHAR(500)     DEFAULT NULL                  COMMENT 'Area perbaikan yang dipilih, dipisah koma',
  `feedback_text` TEXT             DEFAULT NULL                  COMMENT 'Saran / masukan bebas dari pelanggan',
  `ip_address`    VARCHAR(45)      DEFAULT NULL                  COMMENT 'IP address pengisi survey',
  `user_agent`    VARCHAR(500)     DEFAULT NULL                  COMMENT 'Browser / device info',
  `created_at`    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY  `uq_no_nota`       (`no_nota`),
  KEY         `idx_csat_score`   (`csat_score`),
  KEY         `idx_nps_score`    (`nps_score`),
  KEY         `idx_nps_category` (`nps_category`),
  KEY         `idx_created_at`   (`created_at`)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Customer Satisfaction Survey — CSAT & NPS | PT Waschen Alora Indonesia';
