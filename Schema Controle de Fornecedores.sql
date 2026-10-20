-- Esquema inicial do Sistema de Controle de Fornecedores.
-- Este script cria um banco novo e não apaga nem atualiza tabelas existentes.

CREATE DATABASE IF NOT EXISTS fornecedores
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE fornecedores;

CREATE TABLE fornecedores (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome VARCHAR(200) NOT NULL,
    cnpj VARCHAR(18) NOT NULL,
    telefone VARCHAR(20) NOT NULL,
    cidade VARCHAR(200) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_fornecedores_nome (nome),
    KEY idx_fornecedores_cnpj (cnpj)
) ENGINE=InnoDB;

CREATE TABLE compras (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    data DATE NOT NULL,
    valor DECIMAL(12, 2) NOT NULL,
    fornecedores_id INT UNSIGNED NULL,
    status ENUM('pendente', 'pago', 'cancelado') NOT NULL DEFAULT 'pendente',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_compras_fornecedor (fornecedores_id),
    KEY idx_compras_status_data (status, data),
    CONSTRAINT fk_compras_fornecedor
        FOREIGN KEY (fornecedores_id)
        REFERENCES fornecedores (id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE pagamentos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    compra_id INT UNSIGNED NOT NULL,
    valor DECIMAL(12, 2) NOT NULL,
    data DATE NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_pagamentos_compra_data (compra_id, data),
    CONSTRAINT fk_pagamentos_compra
        FOREIGN KEY (compra_id)
        REFERENCES compras (id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB;
