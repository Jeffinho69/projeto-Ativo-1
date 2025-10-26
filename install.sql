

-- Usuários
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  fullName VARCHAR(200) NOT NULL,
  role ENUM('recep','vereador','admin') NOT NULL DEFAULT 'recep',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Visitantes e ações
CREATE TABLE IF NOT EXISTS visitors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  doc VARCHAR(50) NULL,
  council VARCHAR(200) NOT NULL,        -- nome do vereador/setor
  reason VARCHAR(300) NULL,
  added_by VARCHAR(100) NULL,            -- username do recepcionista
  added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  status ENUM('waiting','inside','left') DEFAULT 'waiting',
  entered_at DATETIME NULL,
  left_at DATETIME NULL,
  approved_by VARCHAR(100) NULL,
  exited_by VARCHAR(100) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Index para buscas por date e council
CREATE INDEX idx_added_at ON visitors(added_at);
CREATE INDEX idx_council ON visitors(council);

-- Inserir usuários iniciais (senhas em texto aqui só pra criar; vamos explicar para trocar)
-- Senhas sugeridas (antes de importar, você pode trocar ou depois no painel):
-- Rafael (recepção) senha: Rafael123
-- Shirlane (recepção) senha: Recep123
-- Ana Paula (vereador) senha: Ana123

INSERT INTO users (username, password_hash, fullName, role) VALUES
('Rafael', 'Rafael123', 'Rafael', 'recep'),
('Shirlane', 'Shirlane123', 'Shirlane', 'recep'),
('anapaula', 'Anapaula123', 'Ana Paula', 'vereador');

