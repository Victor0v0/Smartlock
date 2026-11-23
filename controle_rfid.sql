-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 23/11/2026 às 22:20
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `controle_rfid`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `cartoes`
--

CREATE TABLE `cartoes` (
  `id` int(11) NOT NULL,
  `uid` varchar(32) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `pode_acessar` tinyint(1) NOT NULL DEFAULT 0,
  `ativo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `cartoes`
--

INSERT INTO `cartoes` (`id`, `uid`, `usuario_id`, `pode_acessar`, `ativo`) VALUES
(1, '04A1B2C3', 1, 1, 1),
(4, 'AABBCCEE', 4, 1, 1),
(6, '0E933902', 6, 1, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `compartimentos`
--

CREATE TABLE `compartimentos` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `compartimentos`
--

INSERT INTO `compartimentos` (`id`, `nome`, `descricao`, `ativo`) VALUES
(1, 'Sala de Teste', NULL, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `dispositivos`
--

CREATE TABLE `dispositivos` (
  `id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `compartimento_id` int(11) NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `dispositivos`
--

INSERT INTO `dispositivos` (`id`, `token`, `compartimento_id`, `ativo`) VALUES
(1, 'ESP32-SALA-TESTE-001', 1, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `log_acessos`
--

CREATE TABLE `log_acessos` (
  `id` bigint(20) NOT NULL,
  `cartao_id` int(11) DEFAULT NULL,
  `compartimento_id` int(11) DEFAULT NULL,
  `uid_lido` varchar(32) NOT NULL,
  `data_hora` datetime NOT NULL DEFAULT current_timestamp(),
  `resultado` enum('AUTORIZADO','NEGADO') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `log_acessos`
--

INSERT INTO `log_acessos` (`id`, `cartao_id`, `compartimento_id`, `uid_lido`, `data_hora`, `resultado`) VALUES
(1, 1, NULL, '04A1B2C3', '2026-10-31 17:58:24', 'AUTORIZADO'),
(2, NULL, NULL, '04A1B2DD', '2026-10-31 17:59:09', 'NEGADO'),
(3, NULL, NULL, '04A1B2DD', '2026-10-31 17:59:10', 'NEGADO'),
(4, NULL, NULL, '04A1B2DD', '2026-10-31 18:05:55', 'NEGADO'),
(5, NULL, NULL, '04A1B2DD', '2026-11-02 18:41:09', 'NEGADO'),
(6, 1, 1, '04A1B2C3', '2026-11-08 00:48:10', 'AUTORIZADO'),
(7, 4, 1, 'AABBCCDD', '2026-11-08 00:48:10', 'NEGADO'),
(8, 1, 1, '04A1B2C3', '2026-11-08 17:46:16', 'AUTORIZADO'),
(9, 4, 1, 'AABBCCDD', '2026-11-08 17:46:16', 'NEGADO'),
(10, 1, 1, '04A1B2C3', '2026-11-08 18:20:00', 'AUTORIZADO'),
(11, 4, 1, 'AABBCCDD', '2026-11-08 18:20:00', 'NEGADO'),
(12, NULL, NULL, 'AABBCCEE', '2026-11-11 16:58:44', 'NEGADO'),
(13, 4, 1, 'AABBCCEE', '2026-11-11 17:07:44', 'NEGADO'),
(14, 1, 1, '04A1B2C3', '2026-11-11 17:11:35', 'AUTORIZADO'),
(15, NULL, 1, '0E933902', '2026-11-11 18:03:10', 'NEGADO'),
(16, 6, 1, '0E933902', '2026-11-11 18:22:28', 'NEGADO'),
(17, 6, 1, '0E933902', '2026-11-11 18:22:35', 'NEGADO'),
(18, 6, 1, '0E933902', '2026-11-11 18:29:31', 'AUTORIZADO'),
(19, 6, 1, '0E933902', '2026-11-11 18:29:43', 'AUTORIZADO'),
(20, 6, 1, '0E933902', '2026-11-11 18:29:56', 'AUTORIZADO'),
(21, 6, 1, '0E933902', '2026-11-11 18:30:13', 'AUTORIZADO'),
(22, NULL, 1, '4F8562DE', '2026-11-11 18:30:24', 'NEGADO'),
(23, NULL, 1, '4F8562DE', '2026-11-11 18:30:29', 'NEGADO'),
(24, 6, 1, '0E933902', '2026-11-11 18:30:35', 'AUTORIZADO'),
(25, 6, 1, '0E933902', '2026-11-11 18:32:26', 'AUTORIZADO'),
(26, 6, 1, '0E933902', '2026-11-11 14:46:19', 'AUTORIZADO'),
(27, 6, 1, '0E933902', '2026-11-20 10:57:22', 'AUTORIZADO'),
(28, 6, 1, '0E933902', '2026-11-20 11:00:13', 'AUTORIZADO'),
(29, 6, 1, '0E933902', '2026-11-20 11:00:22', 'AUTORIZADO'),
(30, NULL, 1, '4F8562DE', '2026-11-20 11:01:12', 'NEGADO'),
(31, 6, 1, '0E933902', '2026-11-20 11:04:42', 'AUTORIZADO'),
(32, 6, 1, '0E933902', '2026-11-20 11:05:19', 'AUTORIZADO'),
(33, 6, 1, '0E933902', '2026-11-20 11:07:08', 'AUTORIZADO'),
(34, 6, 1, '0E933902', '2026-11-20 11:08:48', 'AUTORIZADO'),
(35, 6, 1, '0E933902', '2026-11-20 11:09:14', 'AUTORIZADO'),
(36, 6, 1, '0E933902', '2026-11-20 11:10:03', 'AUTORIZADO'),
(37, 6, 1, '0E933902', '2026-11-20 11:10:13', 'AUTORIZADO'),
(38, 6, 1, '0E933902', '2026-11-20 11:10:28', 'AUTORIZADO'),
(39, 6, 1, '0E933902', '2026-11-20 11:11:31', 'AUTORIZADO'),
(40, 6, 1, '0E933902', '2026-11-20 11:43:38', 'AUTORIZADO'),
(41, 6, 1, '0E933902', '2026-11-20 11:43:48', 'AUTORIZADO'),
(42, 6, 1, '0E933902', '2026-11-20 11:45:39', 'AUTORIZADO'),
(43, 6, 1, '0E933902', '2026-11-20 11:45:43', 'AUTORIZADO'),
(44, 6, 1, '0E933902', '2026-11-20 11:45:53', 'AUTORIZADO'),
(45, NULL, 1, '4F8562DE', '2026-11-20 11:50:26', 'NEGADO'),
(46, 6, 1, '0E933902', '2026-11-20 12:38:36', 'AUTORIZADO'),
(47, 6, 1, '0E933902', '2026-11-20 13:11:57', 'AUTORIZADO'),
(48, NULL, 1, '4F8562DE', '2026-11-20 13:12:09', 'NEGADO'),
(49, 6, 1, '0E933902', '2026-11-20 13:19:23', 'AUTORIZADO'),
(50, 6, 1, '0E933902', '2026-11-20 13:19:52', 'AUTORIZADO'),
(51, 6, 1, '0E933902', '2026-11-20 13:28:48', 'AUTORIZADO'),
(52, 6, 1, '0E933902', '2026-11-20 13:38:37', 'AUTORIZADO'),
(53, 6, 1, '0E933902', '2026-11-20 13:38:48', 'AUTORIZADO'),
(54, NULL, 1, '4F8562DE', '2026-11-20 13:38:57', 'NEGADO'),
(55, 6, 1, '0E933902', '2026-11-20 13:39:02', 'AUTORIZADO'),
(56, 6, 1, '0E933902', '2026-11-20 14:02:43', 'AUTORIZADO'),
(57, 6, 1, '0E933902', '2026-11-22 21:41:45', 'AUTORIZADO'),
(58, 6, 1, '0E933902', '2026-11-22 21:42:37', 'AUTORIZADO'),
(59, 6, 1, '0E933902', '2026-11-22 21:42:42', 'AUTORIZADO'),
(60, NULL, 1, '4F8562DE', '2026-11-22 21:42:49', 'NEGADO'),
(61, 6, 1, '0E933902', '2026-11-22 21:43:19', 'AUTORIZADO'),
(62, 6, 1, '0E933902', '2026-11-22 21:43:27', 'AUTORIZADO');

-- --------------------------------------------------------

--
-- Estrutura para tabela `permissoes`
--

CREATE TABLE `permissoes` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `compartimento_id` int(11) NOT NULL,
  `ativa` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `permissoes`
--

INSERT INTO `permissoes` (`id`, `usuario_id`, `compartimento_id`, `ativa`) VALUES
(1, 1, 1, 1),
(2, 6, 1, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `ativo`) VALUES
(1, 'Victor Teste', NULL, 1),
(4, 'Victor_Teste_2', NULL, 1),
(5, 'Victor', NULL, 1),
(6, 'Victor', NULL, 1);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `cartoes`
--
ALTER TABLE `cartoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uid` (`uid`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `compartimentos`
--
ALTER TABLE `compartimentos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `dispositivos`
--
ALTER TABLE `dispositivos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_dispositivo_token` (`token`),
  ADD UNIQUE KEY `uk_dispositivo_compartimento` (`compartimento_id`);

--
-- Índices de tabela `log_acessos`
--
ALTER TABLE `log_acessos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cartao_id` (`cartao_id`),
  ADD KEY `idx_data_hora` (`data_hora`),
  ADD KEY `idx_uid_lido` (`uid_lido`),
  ADD KEY `fk_log_compartimento` (`compartimento_id`);

--
-- Índices de tabela `permissoes`
--
ALTER TABLE `permissoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_usuario_compartimento` (`usuario_id`,`compartimento_id`),
  ADD KEY `fk_permissao_compartimento` (`compartimento_id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `cartoes`
--
ALTER TABLE `cartoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `compartimentos`
--
ALTER TABLE `compartimentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `dispositivos`
--
ALTER TABLE `dispositivos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `log_acessos`
--
ALTER TABLE `log_acessos`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT de tabela `permissoes`
--
ALTER TABLE `permissoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `cartoes`
--
ALTER TABLE `cartoes`
  ADD CONSTRAINT `cartoes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `dispositivos`
--
ALTER TABLE `dispositivos`
  ADD CONSTRAINT `fk_dispositivo_compartimento` FOREIGN KEY (`compartimento_id`) REFERENCES `compartimentos` (`id`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `log_acessos`
--
ALTER TABLE `log_acessos`
  ADD CONSTRAINT `fk_log_compartimento` FOREIGN KEY (`compartimento_id`) REFERENCES `compartimentos` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `log_acessos_ibfk_1` FOREIGN KEY (`cartao_id`) REFERENCES `cartoes` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `permissoes`
--
ALTER TABLE `permissoes`
  ADD CONSTRAINT `fk_permissao_compartimento` FOREIGN KEY (`compartimento_id`) REFERENCES `compartimentos` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_permissao_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
