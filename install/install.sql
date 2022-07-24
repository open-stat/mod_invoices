CREATE TABLE `mod_invoices` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `type` enum('house_invoice','membership_fee','other') NOT NULL DEFAULT 'house_invoice',
    `title` varchar(255) DEFAULT NULL,
    `target` varchar(255) DEFAULT NULL,
    `date_invoice` date DEFAULT NULL,
    `invoice_data` text,
    `invoice_text` text,
    `date_created` timestamp NULL DEFAULT NULL,
    `date_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `mod_invoices_files` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `content` longblob,
    `refid` int unsigned NOT NULL,
    `filename` varchar(255) NOT NULL,
    `filesize` int NOT NULL,
    `hash` varchar(128) NOT NULL,
    `type` varchar(20) DEFAULT NULL,
    `fieldid` varchar(255) DEFAULT NULL,
    `thumb` longblob,
    PRIMARY KEY (`id`),
    KEY `idx1_mod_invoices_files` (`refid`),
    CONSTRAINT `fk1_mod_invoices_files` FOREIGN KEY (`refid`) REFERENCES `mod_invoices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;