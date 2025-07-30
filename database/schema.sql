-- database/schema.sql

-- Este script cria as tabelas necessárias para o sistema de agendamento do cabeleireiro
-- e insere dados de exemplo para os serviços.

-- ATENÇÃO: Os comandos DROP TABLE IF EXISTS APAGARÃO TODOS OS DADOS
-- existentes nessas tabelas se elas já existirem no seu banco de dados.
-- Use com cautela, especialmente em ambientes de produção!

DROP TABLE IF EXISTS agendamentos;
DROP TABLE IF EXISTS horarios_bloqueados;
DROP TABLE IF EXISTS servicos;


-- Tabela para armazenar os serviços oferecidos pelo cabeleireiro
CREATE TABLE servicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT, -- Descrição opcional do serviço
    duracao_minutos INT NOT NULL, -- Duração média do serviço em minutos
    preco DECIMAL(10, 2) NOT NULL -- Preço do serviço
);

-- Tabela para registrar horários específicos ou dias inteiros em que o cabeleireiro
-- não estará disponível para agendamentos (ex: feriados, folgas, almoço, reuniões)
CREATE TABLE horarios_bloqueados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    data DATE NOT NULL, -- A data do bloqueio
    hora_inicio TIME,   -- Hora de início do bloqueio (NULL para bloquear o dia inteiro)
    hora_fim TIME,      -- Hora de fim do bloqueio (NULL para bloquear o dia inteiro)
    motivo VARCHAR(255), -- Motivo do bloqueio (ex: "Feriado", "Almoço", "Manutenção")
    -- Garante que não haja bloqueios duplicados para o mesmo intervalo de tempo no mesmo dia
    UNIQUE (data, hora_inicio, hora_fim)
);

-- Tabela principal para armazenar os agendamentos realizados pelos clientes
CREATE TABLE agendamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_nome VARCHAR(255) NOT NULL,
    cliente_email VARCHAR(255) NOT NULL,
    cliente_telefone VARCHAR(20), -- Telefone do cliente (opcional)
    servico_id INT NOT NULL,      -- ID do serviço agendado (chave estrangeira para 'servicos')
    data_agendamento DATE NOT NULL, -- Data do agendamento
    hora_agendamento TIME NOT NULL, -- Hora de início do agendamento
    status ENUM('Confirmado', 'Pendente', 'Cancelado', 'Concluido') DEFAULT 'Pendente', -- Status do agendamento
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Data e hora de criação do registro
    
    -- Definição da chave estrangeira para a tabela 'servicos'
    FOREIGN KEY (servico_id) REFERENCES servicos(id)
        ON DELETE RESTRICT -- Impede a exclusão de um serviço se houver agendamentos associados
        ON UPDATE CASCADE  -- Se o ID de um serviço mudar, atualiza automaticamente nos agendamentos (boa prática)
);

-- INSERÇÃO DE DADOS DE EXEMPLO
-- Dados de exemplo para a tabela 'servicos'
INSERT INTO servicos (nome, descricao, duracao_minutos, preco) VALUES
('Corte Masculino', 'Corte de cabelo padrão para homens, incluindo lavagem e finalização.', 30, 45.00),
('Corte Feminino', 'Corte de cabelo moderno com lavagem, hidratação rápida e secagem.', 60, 80.00),
('Barba', 'Aparar, modelar e finalizar a barba com toalha quente.', 20, 30.00),
('Coloração', 'Aplicação de coloração completa ou mechas. Preço pode variar.', 120, 150.00),
('Hidratação', 'Tratamento capilar intensivo para restaurar brilho e maciez.', 45, 60.00),
('Alisamento', 'Processo de alisamento capilar, valor pode variar pelo comprimento.', 180, 250.00);

-- Dados de exemplo para a tabela 'horarios_bloqueados' (opcional, para testes)
-- Descomente as linhas abaixo se desejar testar bloqueios de horários
-- INSERT INTO horarios_bloqueados (data, hora_inicio, hora_fim, motivo) VALUES
-- ('2025-07-31', '10:00:00', '11:00:00', 'Reuniao de equipe'),
-- ('2025-08-15', NULL, NULL, 'Feriado Nacional - Dia da Independencia'); -- Exemplo de bloqueio de dia inteiro