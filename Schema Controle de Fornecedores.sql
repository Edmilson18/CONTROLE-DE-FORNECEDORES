-- CRIANDO O BANCO DE DADOS FORNECEDORES
CREATE DATABASE fornecedores;
USE fornecedores;

-- CRIANDO AS TABELAS DO BANCO
CREATE TABLE fornecedores (
	id INT NOT NULL AUTO_INCREMENT,
    nome VARCHAR(200) NOT NULL,
    cnpj VARCHAR(14) NOT NULL,
    telefone VARCHAR(14) NOT NULL,
    cidade VARCHAR(200) NOT NULL,
    PRIMARY KEY(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE compras (
	id INT NOT NULL AUTO_INCREMENT,
    data TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    valor FLOAT NOT NULL,
    fornecedores_id INT,
    PRIMARY KEY(id),
    KEY fornecedores_id(fornecedores_id),
    CONSTRAINT fk_fornecedores FOREIGN KEY (fornecedores_id) REFERENCES fornecedores (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE pagamentos (
	id INT NOT NULL AUTO_INCREMENT,
    compra_id INT,
    valor FLOAT,
    data TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(id),
    KEY compra_id(compra_id),
    CONSTRAINT fk_compras FOREIGN KEY (compra_id) REFERENCES compras (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- AJUSTANDO ERROS INICIAIS
ALTER TABLE fornecedores.compras ADD COLUMN status ENUM('pendente', 'pago', 'cancelado') NOT NULL DEFAULT 'pendente';
ALTER TABLE fornecedores MODIFY COLUMN cnpj VARCHAR(18) NOT NULL;
ALTER TABLE pagamentos MODIFY COLUMN data DATE NOT NULL;
ALTER TABLE compras MODIFY COLUMN data DATE NOT NULL;