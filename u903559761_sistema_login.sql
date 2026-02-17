-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Tempo de geração: 20/01/2026 às 17:19
-- Versão do servidor: 11.8.3-MariaDB-log
-- Versão do PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `u903559761_sistema_login`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `api_logs`
--

CREATE TABLE `api_logs` (
  `id` int(11) NOT NULL,
  `farmacia_id` int(11) NOT NULL,
  `endpoint` varchar(50) NOT NULL,
  `method` varchar(10) NOT NULL,
  `request_data` text DEFAULT NULL,
  `response_data` text DEFAULT NULL,
  `status_code` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `campaigns`
--

CREATE TABLE `campaigns` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `content` text NOT NULL,
  `media_url` varchar(255) DEFAULT NULL,
  `media_type` enum('image','video','audio','document') DEFAULT NULL,
  `target_tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`target_tags`)),
  `target_sectors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`target_sectors`)),
  `scheduled_at` datetime DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `finished_at` datetime DEFAULT NULL,
  `status` enum('draft','scheduled','sending','sent','cancelled','paused') DEFAULT 'draft',
  `sent_count` int(11) DEFAULT 0,
  `total_count` int(11) DEFAULT 0,
  `failed_count` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `campaign_logs`
--

CREATE TABLE `campaign_logs` (
  `id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `contact_name` varchar(100) DEFAULT NULL,
  `message_content` text DEFAULT NULL,
  `status` enum('pending','sent','failed','delivered','read') DEFAULT 'pending',
  `sent_at` datetime DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `icone` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `categorias`
--

INSERT INTO `categorias` (`id`, `nome`, `descricao`, `icone`) VALUES
(1, 'Medicamentos', 'Remédios com e sem receita', 'pills'),
(2, 'Dermocosméticos', 'Produtos para cuidados com a pele', 'spa'),
(3, 'Suplementos', 'Vitaminas e suplementos alimentares', 'capsules'),
(4, 'Higiene', 'Produtos de higiene pessoal', 'pump-soap'),
(5, 'Equipamentos', 'Equipamentos médicos e de saúde', 'stethoscope'),
(6, 'Leites e Fórmulas', 'Leites especiais e fórmulas infantis', 'baby-bottle'),
(7, 'Cuidados para Bebês', 'Produtos para cuidados infantis', 'baby'),
(8, 'Seção Infantil', 'Produtos para bebês e crianças', 'child'),
(9, 'Fraldas e Higiene', 'Fraldas e produtos de higiene infantil', 'diaper');

-- --------------------------------------------------------

--
-- Estrutura para tabela `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `chat_session_id` int(11) NOT NULL,
  `sender_type` enum('visitor','admin') NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Despejando dados para a tabela `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `chat_session_id`, `sender_type`, `message`, `is_read`, `created_at`) VALUES
(1, 1, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-02-27 16:32:28'),
(2, 2, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-02-27 16:34:11'),
(3, 2, 'visitor', 'oi', 1, '2025-02-27 16:34:14'),
(4, 2, 'admin', 'oi', 1, '2025-02-27 16:34:20'),
(5, 3, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-02-28 19:29:48'),
(6, 4, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-02-28 19:37:29'),
(7, 5, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-02-28 19:42:09'),
(8, 6, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-02-28 19:46:55'),
(9, 7, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-02-28 19:47:59'),
(10, 8, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-02-28 19:53:43'),
(11, 8, 'visitor', 'oi', 1, '2025-02-28 19:53:47'),
(12, 8, 'admin', 'Boa tarde', 1, '2025-02-28 19:54:21'),
(13, 8, 'visitor', 'Tudo bem?', 1, '2025-02-28 19:54:42'),
(14, 8, 'admin', 'iadkjsn', 1, '2025-02-28 19:55:09'),
(15, 8, 'visitor', 'asdad', 1, '2025-02-28 19:55:15'),
(16, 9, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-03-03 22:06:23'),
(17, 9, 'visitor', 'oi', 1, '2025-03-03 22:06:31'),
(18, 9, 'admin', 'olá, tudo bem? Em que posso ajudar', 1, '2025-03-03 22:06:57'),
(19, 9, 'visitor', 'Não estou conseguindo utilizar o farma-pró, existe algum tutorial?', 1, '2025-03-03 22:07:37'),
(20, 10, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-03-03 22:20:02'),
(21, 10, 'visitor', 'Olá', 1, '2025-03-03 22:20:07'),
(22, 11, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-03-03 22:22:36'),
(23, 11, 'visitor', 'Olá', 1, '2025-03-03 22:22:39'),
(24, 12, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-03-03 22:26:32'),
(25, 12, 'visitor', 'Olá', 1, '2025-03-03 22:26:43'),
(26, 12, 'visitor', 'Olá', 1, '2025-03-03 22:31:35'),
(27, 12, 'visitor', 'Olá boa noite', 1, '2025-03-03 22:36:45'),
(28, 12, 'admin', 'Olá', 1, '2025-03-03 22:36:59'),
(29, 12, 'visitor', 'Olá boa noite', 1, '2025-03-03 22:47:15'),
(30, 13, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-03-03 22:51:00'),
(31, 13, 'visitor', 'Olá boa noite', 1, '2025-03-03 22:51:05'),
(32, 13, 'admin', 'Olá', 1, '2025-03-03 22:52:11'),
(33, 13, 'visitor', 'Tudo certo', 1, '2025-03-03 22:52:21'),
(34, 14, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-03-03 22:57:57'),
(35, 14, 'visitor', 'OLÁ', 1, '2025-03-03 22:58:00'),
(36, 15, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-03-03 22:59:17'),
(37, 15, 'visitor', 'Olá', 1, '2025-03-03 22:59:21'),
(38, 16, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-03-03 23:19:07'),
(39, 17, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-03-04 14:16:06'),
(40, 18, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-03-04 16:43:24'),
(41, 18, 'visitor', 'Olá', 1, '2025-03-04 16:43:29'),
(42, 18, 'visitor', 'Olá', 1, '2025-03-04 16:45:38'),
(43, 18, 'visitor', 'Olá', 1, '2025-03-04 16:52:22'),
(44, 18, 'visitor', 'Olá', 1, '2025-03-04 16:53:00'),
(45, 18, 'visitor', 'Oi', 1, '2025-03-04 16:53:14'),
(46, 18, 'visitor', 'Olá', 1, '2025-03-04 17:01:34'),
(47, 18, 'visitor', 'Ola', 1, '2025-03-04 17:02:22'),
(48, 19, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-03-04 17:08:56'),
(49, 19, 'visitor', 'Olá', 1, '2025-03-04 17:09:03'),
(50, 19, 'visitor', 'Oi', 1, '2025-03-04 17:09:34'),
(51, 19, 'visitor', 'Oi', 1, '2025-03-04 17:11:25'),
(52, 19, 'visitor', 'Olá boa noite', 1, '2025-03-04 17:12:47'),
(53, 19, 'visitor', 'Oi', 1, '2025-03-04 17:14:40'),
(54, 19, 'visitor', 'Oi', 1, '2025-03-04 17:21:10'),
(55, 19, 'visitor', 'Olá', 1, '2025-03-04 17:22:43'),
(56, 20, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-03-04 17:33:07'),
(57, 20, 'visitor', 'Olá boa noite', 1, '2025-03-04 17:33:12'),
(58, 21, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-03-04 17:34:17'),
(59, 21, 'visitor', 'Boa Tarde!', 1, '2025-03-04 17:34:28'),
(60, 22, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-03-04 17:46:07'),
(61, 22, 'visitor', 'Olá', 1, '2025-03-04 17:46:13'),
(62, 23, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-03-04 17:47:37'),
(63, 23, 'visitor', 'Olá boa noite', 1, '2025-03-04 17:47:42'),
(64, 23, 'admin', 'Olá', 1, '2025-03-04 17:48:30'),
(65, 23, 'visitor', 'Certo', 1, '2025-03-04 17:48:40'),
(66, 23, 'visitor', 'Opa', 1, '2025-03-04 17:49:00'),
(67, 24, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-03-04 23:05:05'),
(68, 24, 'visitor', 'Olá', 1, '2025-03-04 23:05:09'),
(69, 24, 'visitor', 'Oi', 1, '2025-03-04 23:05:39'),
(70, 24, 'visitor', 'Boa noite', 1, '2025-03-04 23:09:02'),
(71, 24, 'admin', 'olá', 1, '2025-03-04 23:09:19'),
(72, 24, 'visitor', 'Olá boa noite', 1, '2025-03-04 23:09:33'),
(73, 24, 'admin', 'Em que posso ajudar?', 1, '2025-03-04 23:09:56'),
(74, 24, 'visitor', 'Preciso de auxílio', 1, '2025-03-04 23:12:24'),
(75, 24, 'visitor', 'Ajude', 1, '2025-03-04 23:12:48'),
(76, 25, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-03-05 11:54:30'),
(77, 26, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-03-05 14:49:29'),
(78, 26, 'visitor', 'Olá bom dia!', 1, '2025-03-05 14:49:44'),
(79, 26, 'admin', 'Olá, em que posso ajudar?', 1, '2025-03-05 14:50:24'),
(80, 26, 'visitor', 'Não consigo acessar minha conta!', 1, '2025-03-05 14:50:44'),
(81, 27, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-03-05 14:52:00'),
(82, 27, 'visitor', 'Olá boa noite', 1, '2025-03-05 14:52:08'),
(83, 28, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-03-05 15:44:44'),
(84, 29, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-03-06 02:41:11'),
(85, 30, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-03-06 13:58:56'),
(86, 30, 'visitor', 'Olá', 1, '2025-03-06 13:59:03'),
(87, 30, 'visitor', 'Olá boa noite', 1, '2025-03-06 13:59:43'),
(88, 30, 'visitor', 'asdad', 1, '2025-03-06 14:00:25'),
(89, 30, 'visitor', 'Olá', 1, '2025-03-06 14:00:50'),
(90, 31, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-03-07 00:57:05'),
(91, 32, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-03-07 01:05:29'),
(92, 33, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-03-08 03:03:53'),
(93, 34, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-03-10 14:38:00'),
(94, 34, 'visitor', 'Olá', 1, '2025-03-10 14:38:09'),
(95, 35, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-03-17 15:43:11'),
(96, 35, 'visitor', 'Bom dia', 1, '2025-03-17 15:43:15'),
(97, 35, 'admin', 'Bom dia', 1, '2025-03-17 15:43:48'),
(98, 35, 'visitor', 'Eu não consigo entrar com meu acesso, não lembro a senha de acesso!', 1, '2025-03-17 15:44:24'),
(99, 35, 'visitor', 'Pode me ajudar?', 1, '2025-03-17 15:44:48'),
(100, 35, 'visitor', 'Olá', 1, '2025-03-17 15:45:11'),
(101, 35, 'admin', 'Olá, sim pode me passar seu contato por favor? ou prefere que informe por aqui? \nPreciso primeiramente do seu login para localizar sua senha', 1, '2025-03-17 15:46:07'),
(102, 35, 'visitor', 'Valdecir', 1, '2025-03-17 15:46:23'),
(103, 35, 'admin', 'Sua senha é: @senha@\n\nPosso ajudar em mais alguma coisa? vou aguardar a confimação de senha correta', 1, '2025-03-17 15:47:25'),
(104, 35, 'visitor', 'OK', 1, '2025-03-17 15:47:30'),
(105, 35, 'visitor', 'Correto', 1, '2025-03-17 15:47:45'),
(106, 35, 'visitor', 'Obrigado', 1, '2025-03-17 15:47:50'),
(107, 35, 'admin', 'Imagina, eu que agradeço', 1, '2025-03-17 15:48:01'),
(108, 36, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-03-18 17:08:34'),
(109, 36, 'visitor', 'Opa', 1, '2025-03-18 17:08:36'),
(110, 36, 'visitor', 'Opa', 1, '2025-03-18 17:09:00'),
(111, 36, 'visitor', 'Olá', 1, '2025-03-18 17:09:25'),
(112, 36, 'admin', 'ok', 1, '2025-03-18 17:09:41'),
(113, 37, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-03-18 17:18:15'),
(114, 37, 'visitor', 'Olá', 1, '2025-03-18 17:18:18'),
(115, 38, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-03-18 17:20:11'),
(116, 38, 'visitor', 'Oii', 1, '2025-03-18 17:20:13'),
(117, 39, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-03-18 17:52:52'),
(118, 39, 'visitor', 'Olá', 1, '2025-03-18 17:52:56'),
(119, 39, 'visitor', 'Olá', 1, '2025-03-18 17:53:14'),
(120, 40, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-03-18 17:53:48'),
(121, 40, 'visitor', 'Opa', 1, '2025-03-18 17:53:55'),
(122, 41, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-03-19 13:19:43'),
(123, 41, 'visitor', 'Olá boa noite', 1, '2025-03-19 13:19:49'),
(124, 41, 'visitor', 'Tudo certo?', 1, '2025-03-19 13:20:01'),
(125, 41, 'visitor', 'Preciso de auxilio', 1, '2025-03-19 13:20:09'),
(126, 41, 'visitor', 'Pode me ajudar', 1, '2025-03-19 13:20:29'),
(127, 42, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-03-19 13:23:28'),
(128, 42, 'visitor', 'Olá', 1, '2025-03-19 13:23:31'),
(129, 43, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-03-19 13:26:15'),
(130, 43, 'visitor', 'oi', 1, '2025-03-19 13:26:17'),
(131, 44, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-04-08 15:47:18'),
(132, 44, 'visitor', 'Olá', 1, '2025-04-08 15:47:24'),
(133, 45, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-04-14 01:26:30'),
(134, 45, 'visitor', 'asdsad', 1, '2025-04-14 01:26:45'),
(135, 45, 'admin', 'asdsadsad', 1, '2025-04-14 01:26:50'),
(136, 45, 'visitor', 'adasdasdad', 1, '2025-04-14 01:26:54'),
(137, 45, 'visitor', 'sadsadsa', 1, '2025-04-14 01:59:10'),
(138, 45, 'admin', 'asdsadsad', 1, '2025-04-14 01:59:23'),
(139, 45, 'visitor', 'Olá', 1, '2025-04-14 01:59:30'),
(140, 46, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-05-12 18:20:10'),
(141, 47, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-06-12 23:19:45'),
(142, 48, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-07-24 16:53:47'),
(143, 48, 'visitor', 'nao estou conseguindo acessar a minha conta da como credencial invalida', 1, '2025-07-24 16:54:51'),
(144, 48, 'admin', 'Boa Tarde tudo bem?', 1, '2025-07-24 16:55:58'),
(145, 48, 'visitor', 'boa tarde tudo sim', 1, '2025-07-24 16:56:19'),
(146, 48, 'admin', 'Primeiramente desculpe o transtorno, estamos lançando novas ferramentas, e pode ter dado algum problema, pode me enviar seu id ou senha?', 1, '2025-07-24 16:56:42'),
(147, 48, 'visitor', '35242436', 1, '2025-07-24 16:57:42'),
(148, 48, 'visitor', 'essa e a senha', 1, '2025-07-24 16:57:48'),
(149, 48, 'visitor', 'id seria o email?', 1, '2025-07-24 16:58:05'),
(150, 48, 'admin', 'Sim', 1, '2025-07-24 16:58:17'),
(151, 48, 'visitor', 'farmaciaprocofarma@yahoo.com.br', 1, '2025-07-24 16:58:52'),
(152, 48, 'admin', 'Um minuto', 1, '2025-07-24 16:59:04'),
(153, 48, 'visitor', 'ok', 1, '2025-07-24 16:59:09'),
(154, 48, 'admin', 'Id: farmaciaprocofarma\nEmail: farmaciaprocofarma@yahoo.com.br\nSenha: 35242436\n\nVocê pode usar id ou email para fazer o login, tanto faz, pode testar que agora vai dar certo', 1, '2025-07-24 17:00:31'),
(155, 48, 'visitor', 'ok obrigado', 1, '2025-07-24 17:01:01'),
(156, 48, 'admin', 'Novamente peço desculpas pelo transtorno, qualquer coisa estamos disponível', 1, '2025-07-24 17:01:02'),
(157, 48, 'admin', 'Vou encerrar nossa conversa por aqui, muito obrigado', 1, '2025-07-24 17:02:47'),
(158, 49, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-07-24 18:39:14'),
(159, 49, 'visitor', 'Boa tarde', 1, '2025-07-24 18:39:20'),
(160, 50, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-07-24 18:39:56'),
(161, 50, 'visitor', 'Olá', 1, '2025-07-24 18:40:01'),
(162, 51, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-07-24 18:40:22'),
(163, 52, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-07-24 19:11:22'),
(164, 52, 'visitor', 'Olá', 1, '2025-07-24 19:11:30'),
(165, 52, 'admin', 'Olá, envie o seu id de login por favor para que eu te localize', 0, '2025-07-24 19:12:08'),
(166, 53, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-07-24 19:12:28'),
(167, 54, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-07-24 19:19:02'),
(168, 54, 'visitor', 'Boa tarde', 1, '2025-07-24 19:19:09'),
(169, 54, 'visitor', 'Tudo bem?', 1, '2025-07-24 19:19:20'),
(170, 54, 'admin', 'Olá, tudo sim e com vc? Como posso ser util?', 0, '2025-07-24 19:19:42'),
(171, 55, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-07-24 19:19:59'),
(172, 56, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-07-24 20:03:46'),
(173, 56, 'visitor', 'Olá', 1, '2025-07-24 20:03:58'),
(174, 56, 'admin', 'Olá', 1, '2025-07-24 20:04:04'),
(175, 57, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-07-24 20:04:27'),
(176, 57, 'visitor', 'olá', 1, '2025-07-24 20:09:46'),
(177, 57, 'admin', 'oi', 1, '2025-07-24 20:10:00'),
(178, 58, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-07-24 20:10:31'),
(179, 58, 'visitor', 'oi', 1, '2025-07-24 20:15:04'),
(180, 59, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-07-24 20:15:52'),
(181, 59, 'visitor', 'oi', 1, '2025-07-24 20:18:48'),
(182, 60, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-07-24 20:19:37'),
(183, 60, 'visitor', 'opa', 1, '2025-07-24 20:22:09'),
(184, 61, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-07-24 20:22:47'),
(185, 62, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-07-24 20:28:15'),
(186, 62, 'visitor', 'ola', 1, '2025-07-24 20:28:23'),
(187, 61, 'visitor', 'Olá', 1, '2025-07-24 20:28:56'),
(188, 63, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-08-01 18:24:46'),
(189, 63, 'visitor', 'olá boa tarde! tudo bem? sou novo usuario', 1, '2025-08-01 18:24:59'),
(190, 63, 'visitor', 'preciso da permissão pra poder acessar', 1, '2025-08-01 18:25:24'),
(191, 63, 'visitor', 'ja fiz o meu registro', 1, '2025-08-01 18:25:30'),
(192, 63, 'visitor', 'como proceder???', 1, '2025-08-01 18:51:05'),
(193, 63, 'admin', 'Estou liberando', 1, '2025-08-01 18:56:45'),
(194, 63, 'visitor', 'ok', 1, '2025-08-01 18:58:05'),
(195, 63, 'visitor', 'assim que liberar só me da um toque aqui fazendo favor', 1, '2025-08-01 18:58:22'),
(196, 63, 'visitor', 'blz', 1, '2025-08-01 18:58:24'),
(197, 63, 'admin', 'Liberado, qualquer coisa estamos disponivel', 1, '2025-08-01 18:59:21'),
(198, 63, 'admin', 'Se percer acesso ou duvidas pode pedir aqui ou envia lá no whatsapp', 1, '2025-08-01 18:59:48'),
(199, 63, 'visitor', 'blz entao', 1, '2025-08-01 19:00:28'),
(200, 63, 'visitor', 'muito obrigado', 1, '2025-08-01 19:00:31'),
(201, 63, 'admin', 'Eu que agradeço', 0, '2025-08-01 19:01:30'),
(202, 64, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-08-02 21:15:10'),
(203, 64, 'visitor', 'Oi tudo bem! ✅ Cache salvo no navegador: a1823da15f0e4cb6583b9540159e8a10', 1, '2025-08-02 21:15:37'),
(204, 64, 'admin', 'Bom dia', 0, '2025-08-04 12:08:46'),
(205, 65, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-08-24 00:04:25'),
(206, 65, 'visitor', 'Opa', 1, '2025-08-24 00:04:28'),
(207, 66, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-08-24 00:05:37'),
(208, 66, 'visitor', 'Olá boa noite', 1, '2025-08-24 00:05:45'),
(209, 66, 'admin', 'opa', 1, '2025-08-24 00:05:56'),
(210, 66, 'visitor', 'oi', 1, '2025-08-24 00:06:01'),
(211, 67, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-10-21 18:18:37'),
(212, 67, 'visitor', 'Oi', 1, '2025-10-21 18:19:14'),
(213, 68, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-10-24 11:42:41'),
(214, 68, 'visitor', 'oi', 1, '2025-10-24 11:49:15'),
(215, 69, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-10-24 11:50:18'),
(216, 69, 'visitor', 'Olá', 1, '2025-10-24 11:50:25'),
(217, 70, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-10-24 12:33:52'),
(218, 70, 'visitor', 'oi', 1, '2025-10-24 12:33:56'),
(219, 71, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-11-13 17:09:20'),
(220, 71, 'visitor', 'Olá boa noite', 1, '2025-11-13 17:09:25'),
(221, 72, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-11-13 17:35:29'),
(222, 72, 'visitor', 'Oi! É a Cinara', 1, '2025-11-13 17:35:40'),
(223, 72, 'admin', 'Boa tarde', 1, '2025-11-13 17:36:33'),
(224, 72, 'visitor', 'Boa tarde', 1, '2025-11-13 17:40:23'),
(225, 72, 'visitor', 'Valdecir, a plataforma está fora do ar há um mês e não sei o que houve. Poderia me auxiliar?', 1, '2025-11-13 17:40:57'),
(226, 72, 'admin', 'Sim, eu vou apresentar um projeto na banca do mestrado amanhã, eu consigo te dar suporte sábado de manhã', 1, '2025-11-13 17:42:39'),
(227, 72, 'admin', 'Consegue? ai já conversamos', 1, '2025-11-13 17:42:50'),
(228, 72, 'visitor', 'Parabéns!!! Posso sim', 1, '2025-11-13 17:49:53'),
(229, 72, 'visitor', 'Vc quer que te ajude na revisão? Eu faço esse tipo de mentoria.', 1, '2025-11-13 17:50:39'),
(230, 72, 'admin', 'Eu to meio inseguro, mais eu estou com o projeto &quot;pronto&quot; que já ajuda eles aprovarem, o projeto é bom e enorme, e como envolve IA provavelmente passa. Só que não vou fazer se não derem bolsa 100%.', 1, '2025-11-13 17:57:49'),
(231, 72, 'visitor', 'Que bom!!! Qual é o tema? Fico muito feliz por vc!', 1, '2025-11-13 17:59:18'),
(232, 72, 'admin', 'Estou aguardando o Sebrae, mais vou apresentar em uma feira de tecnologia, em curitiba, oque já ajuda convencer.', 1, '2025-11-13 17:59:32'),
(233, 72, 'admin', 'Eu criei uma ecosistema interligado que resolve o problema de Marketing do varejo.', 1, '2025-11-13 18:00:19'),
(234, 72, 'admin', 'Resultado de 57% de aumento nos pedidos ( mais estou passando 30%) , e 150% de aumento na produtividade da equipe', 1, '2025-11-13 18:02:32'),
(235, 72, 'visitor', 'Que tudo', 1, '2025-11-13 18:48:56'),
(236, 72, 'visitor', 'Parabéns!!!', 1, '2025-11-13 18:49:01'),
(237, 73, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-11-18 02:19:09'),
(238, 73, 'visitor', 'Olá', 1, '2025-11-18 02:19:15'),
(239, 74, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-12-14 19:19:19'),
(240, 75, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2025-12-23 03:51:57'),
(241, 76, 'visitor', 'Opa', 1, '2026-01-02 20:21:50'),
(242, 76, 'visitor', 'e ai', 1, '2026-01-02 20:23:38'),
(243, 76, 'visitor', 'Salve', 1, '2026-01-02 20:25:15'),
(244, 76, 'visitor', 'Tudo bem?', 1, '2026-01-02 20:27:54'),
(245, 76, 'visitor', 'Muito boa tarde!', 1, '2026-01-02 20:29:38'),
(246, 76, 'visitor', 'Olá', 1, '2026-01-02 20:37:39'),
(247, 77, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2026-01-02 20:38:14'),
(248, 77, 'visitor', 'E ei', 1, '2026-01-02 20:38:16'),
(249, 78, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2026-01-02 20:41:36'),
(250, 78, 'visitor', 'Salve', 1, '2026-01-02 20:41:39'),
(251, 78, 'admin', 'Olá', 1, '2026-01-02 20:41:52'),
(252, 79, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2026-01-02 20:45:28'),
(253, 79, 'visitor', 'Olá', 1, '2026-01-02 20:45:31'),
(254, 79, 'admin', 'Oi', 1, '2026-01-02 20:45:40'),
(255, 79, 'visitor', 'Cara, eu não estou conseguindo acessar, pode me ajudar a ver?', 1, '2026-01-02 20:49:04'),
(256, 79, 'admin', 'Claro, qual é o seu id? Vou verificar.', 1, '2026-01-02 20:49:45'),
(257, 79, 'visitor', 'valdecir@vediese.com,br', 1, '2026-01-02 20:50:25'),
(258, 79, 'admin', 'Tente usar a senha teste 123 e depois troque por favor ?', 1, '2026-01-02 20:51:04'),
(259, 79, 'visitor', 'Obrigado, top de mais', 1, '2026-01-02 20:51:35'),
(260, 76, 'visitor', 'Opa', 1, '2026-01-02 21:06:16'),
(261, 80, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2026-01-06 16:54:51'),
(262, 80, 'visitor', 'Olá', 1, '2026-01-06 16:54:57'),
(263, 81, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2026-01-08 13:28:39'),
(264, 81, 'visitor', 'Oi', 1, '2026-01-08 13:28:45'),
(265, 82, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2026-01-10 04:40:08'),
(266, 82, 'visitor', 'Oi', 1, '2026-01-10 04:40:11'),
(267, 82, 'visitor', 'Oi', 1, '2026-01-10 04:49:36'),
(268, 82, 'visitor', 'Olá', 1, '2026-01-10 04:50:08'),
(269, 82, 'visitor', 'Oi', 1, '2026-01-10 04:53:07'),
(270, 82, 'visitor', 'Olá', 1, '2026-01-10 04:53:21'),
(271, 83, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2026-01-10 04:53:45'),
(272, 83, 'visitor', 'Ok', 1, '2026-01-10 04:53:49'),
(273, 84, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2026-01-11 20:48:19'),
(274, 85, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2026-01-11 20:48:42'),
(275, 86, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2026-01-11 20:52:32'),
(276, 86, 'visitor', 'Apenas um teste', 1, '2026-01-11 20:53:13'),
(277, 86, 'visitor', '2', 1, '2026-01-11 20:53:29'),
(278, 86, 'visitor', 'Tudo bem?', 1, '2026-01-11 20:54:18'),
(279, 86, 'admin', 'Olá ', 1, '2026-01-11 20:54:40'),
(280, 86, 'admin', 'Em que posso ajudar? ', 1, '2026-01-11 20:54:55'),
(281, 86, 'admin', 'Meu nome é Valdecir, vou ser seu suporte Apartir de agora. Em que posso ajudá-lo?', 1, '2026-01-11 20:55:32'),
(282, 86, 'visitor', 'Não consigo entrar', 1, '2026-01-11 20:55:58'),
(283, 86, 'admin', 'Me envie o e-mail que você cadastrou por favor? ', 1, '2026-01-11 20:56:27'),
(284, 86, 'visitor', 'valdecir.dossantos@hotmail.com', 1, '2026-01-11 20:56:52'),
(285, 86, 'admin', 'Certo, sua nova senha é Teste123*', 1, '2026-01-11 20:58:12'),
(286, 86, 'visitor', 'Deu cert', 1, '2026-01-11 20:58:24'),
(287, 86, 'admin', 'Farmapro.app.br', 1, '2026-01-11 20:58:33'),
(288, 86, 'admin', 'Vai para a página inicial, cliente na bolinha roça no canto superior direito ', 1, '2026-01-11 20:59:00'),
(289, 86, 'admin', 'Agora clique em configurações e mude sua senha ', 1, '2026-01-11 20:59:29'),
(290, 86, 'admin', 'Muito obrigado ', 1, '2026-01-11 20:59:53'),
(291, 86, 'visitor', 'Certo, mudei, eu que agradeço', 1, '2026-01-11 21:00:22'),
(292, 87, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2026-01-12 11:41:18'),
(293, 87, 'visitor', 'oi', 1, '2026-01-12 11:41:22'),
(294, 88, 'admin', 'Olá! Bem-vindo ao suporte da FarmaPro. Como posso ajudar você hoje?', 1, '2026-01-16 18:30:12'),
(295, 88, 'visitor', 'olá', 0, '2026-01-16 18:30:14'),
(296, 88, 'visitor', 'oi', 0, '2026-01-18 17:52:04');

-- --------------------------------------------------------

--
-- Estrutura para tabela `chat_sessions`
--

CREATE TABLE `chat_sessions` (
  `id` int(11) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `visitor_name` varchar(255) DEFAULT NULL,
  `visitor_email` varchar(255) DEFAULT NULL,
  `status` enum('ativo','fechado','aguardando') NOT NULL DEFAULT 'ativo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `chatwoot_contact_identifier` varchar(255) DEFAULT NULL,
  `chatwoot_conversation_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Despejando dados para a tabela `chat_sessions`
--

INSERT INTO `chat_sessions` (`id`, `session_id`, `visitor_name`, `visitor_email`, `status`, `created_at`, `updated_at`, `last_activity`, `ip_address`, `chatwoot_contact_identifier`, `chatwoot_conversation_id`) VALUES
(1, '1275c07917408f8ddaa68fae43dd2bed', 'Visitante', '', 'fechado', '2025-02-27 16:32:28', '2025-02-27 16:32:43', '2025-02-27 16:32:28', '177.39.209.43', NULL, NULL),
(2, '1eaabafbbc3189ad880e7517c8d381eb', 'Visitante', '', 'fechado', '2025-02-27 16:34:11', '2025-02-27 16:34:28', '2025-02-27 16:34:20', '177.39.209.43', NULL, NULL),
(3, '3e52401c0b613371b714287976d230f7', 'Visitante', '', 'fechado', '2025-02-28 19:29:48', '2025-02-28 19:53:14', '2025-02-28 19:29:48', '177.39.209.43', NULL, NULL),
(4, 'e0bbccc136575178119dee2b0972287a', 'Visitante', '', 'fechado', '2025-02-28 19:37:29', '2025-02-28 19:53:11', '2025-02-28 19:37:29', '177.39.209.43', NULL, NULL),
(5, '74513a3f28a2dcf6941d5ce52efc99e1', 'Visitante', '', 'fechado', '2025-02-28 19:42:09', '2025-02-28 19:53:07', '2025-02-28 19:42:09', '177.39.209.43', NULL, NULL),
(6, '1f97d89e86b520ebc6f510d4bf80ba9b', 'Visitante', '', 'fechado', '2025-02-28 19:46:55', '2025-02-28 19:49:44', '2025-02-28 19:46:55', '177.39.209.43', NULL, NULL),
(7, 'fdda6704a0d9855ee9570e0a6477bd92', 'Visitante', '', 'fechado', '2025-02-28 19:47:59', '2025-02-28 19:49:40', '2025-02-28 19:47:59', '177.39.209.43', NULL, NULL),
(8, 'f3602f1a9d3e918ce835f74af8c1d672', 'Visitante', '', 'fechado', '2025-02-28 19:53:43', '2025-02-28 19:55:29', '2025-02-28 19:55:15', '177.39.209.43', NULL, NULL),
(9, '2676a12340f1c26bd6e25536fd41c5aa', 'Visitante', '', 'fechado', '2025-03-03 22:06:23', '2025-03-03 22:08:22', '2025-03-03 22:08:06', '45.167.151.128', NULL, NULL),
(10, '9452fe305185fb79716ac60daab914ad', 'Visitante', '', 'fechado', '2025-03-03 22:20:02', '2025-03-03 22:20:35', '2025-03-03 22:20:07', '45.167.151.128', NULL, NULL),
(11, 'dd32fd45455403f2500242eeddcd5b69', 'Visitante', '', 'fechado', '2025-03-03 22:22:36', '2025-03-03 22:25:37', '2025-03-03 22:22:39', '45.167.151.128', NULL, NULL),
(12, '4a957962e5a4ab78333f0c59d04ca9da', 'Visitante', '', 'fechado', '2025-03-03 22:26:32', '2025-03-03 22:50:43', '2025-03-03 22:47:47', '45.167.151.128', NULL, NULL),
(13, 'd63329aae882249da2e591b8a50d89a9', 'Visitante', '', 'fechado', '2025-03-03 22:51:00', '2025-03-03 22:58:16', '2025-03-03 22:57:17', '45.167.151.128', NULL, NULL),
(14, '7c19344f0f800a9b31d4c39c096bf526', 'Visitante', '', 'fechado', '2025-03-03 22:57:57', '2025-03-03 22:58:21', '2025-03-03 22:58:00', '45.167.151.128', NULL, NULL),
(15, '6aac169e377f38084f25173ab20df6ac', 'Visitante', '', 'fechado', '2025-03-03 22:59:17', '2025-03-03 22:59:37', '2025-03-03 22:59:21', '45.167.151.128', NULL, NULL),
(16, '6139e6ec0c1cd17cc1bbd1cbd455ef4c', 'Visitante', '', 'fechado', '2025-03-03 23:19:07', '2025-03-04 14:17:26', '2025-03-03 23:19:07', '45.167.151.128', NULL, NULL),
(17, '8c04b5e166ed1a0706e2707d48ddf795', 'Visitante', '', 'fechado', '2025-03-04 14:16:06', '2025-03-04 14:17:23', '2025-03-04 14:16:06', '45.167.151.128', NULL, NULL),
(18, '1234bc244c6bdce75aea57a504563028', 'Visitante', '', 'fechado', '2025-03-04 16:43:24', '2025-03-04 17:09:18', '2025-03-04 17:02:22', '45.167.151.128', NULL, NULL),
(19, '235b8874704e3ff78f2edb3f89aa0cf8', 'Visitante', '', 'fechado', '2025-03-04 17:08:56', '2025-03-04 17:22:58', '2025-03-04 17:22:43', '45.167.151.128', NULL, NULL),
(20, '5bd4a6bec0de1362cef8d6515ce36372', 'Visitante', '', 'fechado', '2025-03-04 17:33:07', '2025-03-04 17:33:48', '2025-03-04 17:33:12', '45.167.151.128', NULL, NULL),
(21, '27458181c0eb5309be3be0a91823422c', 'Visitante', '', 'fechado', '2025-03-04 17:34:17', '2025-03-04 17:37:43', '2025-03-04 17:34:28', '45.167.151.128', NULL, NULL),
(22, 'fdeb93ee64ad0e02bd838c6da889fd7a', 'Visitante', '', 'fechado', '2025-03-04 17:46:07', '2025-03-04 17:46:53', '2025-03-04 17:46:13', '45.167.151.128', NULL, NULL),
(23, '690f2c2ff9f20faf861dce0d3691efff', 'Visitante', '', 'fechado', '2025-03-04 17:47:37', '2025-03-04 17:49:26', '2025-03-04 17:49:00', '45.167.151.128', NULL, NULL),
(24, 'c32103c52d292ca1fe3fc9110ca3f806', 'Visitante', '', 'fechado', '2025-03-04 23:05:05', '2025-03-04 23:13:03', '2025-03-04 23:12:48', '45.167.151.128', NULL, NULL),
(25, 'b49bb9359010d8000d00c94245776161', 'Visitante', '', 'fechado', '2025-03-05 11:54:30', '2025-03-05 11:55:12', '2025-03-05 11:54:30', '177.39.209.43', NULL, NULL),
(26, '984a54676d8dd809323c4c2fc25275bf', 'Visitante', '', 'fechado', '2025-03-05 14:49:29', '2025-03-05 14:52:58', '2025-03-05 14:50:44', '177.39.209.43', NULL, NULL),
(27, '28aa7812128bedd92ae00e3f53797fce', 'Visitante', '', 'fechado', '2025-03-05 14:52:00', '2025-03-05 14:52:51', '2025-03-05 14:52:08', '177.39.209.43', NULL, NULL),
(28, 'da0c35539f2c538065b3f29c7d39fd36', 'Visitante', '', 'fechado', '2025-03-05 15:44:44', '2025-03-05 19:26:25', '2025-03-05 15:44:44', '177.39.209.43', NULL, NULL),
(29, 'bde862a21af0373f53491f6084d5d4f8', 'Visitante', '', 'fechado', '2025-03-06 02:41:11', '2025-03-06 13:52:13', '2025-03-06 02:41:11', '45.167.151.128', NULL, NULL),
(30, '03e75b2455193e2f9d686319e92f8760', 'Visitante', '', 'fechado', '2025-03-06 13:58:56', '2025-03-06 14:01:12', '2025-03-06 14:00:50', '177.39.209.43', NULL, NULL),
(31, '305a92755fb579d25ebca83932cf8e6c', 'Visitante', '', 'fechado', '2025-03-07 00:57:05', '2025-03-08 18:35:05', '2025-03-07 00:57:05', '45.167.151.181', NULL, NULL),
(32, '6b8973191b7f9affac7b7d84c8a9ac19', 'Visitante', '', 'fechado', '2025-03-07 01:05:29', '2025-03-08 18:35:01', '2025-03-07 01:05:29', '45.167.151.181', NULL, NULL),
(33, '7090fa611bb04064fad5d0de1dfae7c7', 'Visitante', '', 'fechado', '2025-03-08 03:03:53', '2025-03-08 18:34:58', '2025-03-08 03:03:53', '45.167.151.114', NULL, NULL),
(34, '5d00e02aa960d3afa04ce2a72f92aab3', 'Visitante', '', 'fechado', '2025-03-10 14:38:00', '2025-03-10 14:38:26', '2025-03-10 14:38:09', '177.39.209.43', NULL, NULL),
(35, 'bd3ad7d91d676255c4c38b71875bc40a', 'Visitante', '', 'fechado', '2025-03-17 15:43:11', '2025-03-17 15:48:58', '2025-03-17 15:48:50', '177.39.209.43', NULL, NULL),
(36, '1252a3f5a1e2becf0b72219ac8cb5c98', 'Visitante', '', 'fechado', '2025-03-18 17:08:34', '2025-03-18 17:09:50', '2025-03-18 17:09:41', '177.39.209.43', NULL, NULL),
(37, 'b3b225a200d8898833c052536301eb65', 'Visitante', '', 'fechado', '2025-03-18 17:18:15', '2025-03-18 17:18:34', '2025-03-18 17:18:18', '177.39.209.43', NULL, NULL),
(38, '2a98fcbf97cbfc912019883b12cd06a1', 'Visitante', '', 'fechado', '2025-03-18 17:20:11', '2025-03-18 17:26:43', '2025-03-18 17:20:13', '177.39.209.43', NULL, NULL),
(39, 'bcf07383daa55757cfba3e8f493145e5', 'Visitante', '', 'fechado', '2025-03-18 17:52:52', '2025-03-18 17:53:31', '2025-03-18 17:53:14', '177.39.209.43', NULL, NULL),
(40, 'daef389fe60c9db07e37a23edb7250a7', 'Visitante', '', 'fechado', '2025-03-18 17:53:48', '2025-03-18 17:54:49', '2025-03-18 17:53:55', '177.39.209.43', NULL, NULL),
(41, '4567c7502094370c24e853910e48d804', 'Visitante', '', 'fechado', '2025-03-19 13:19:43', '2025-03-19 13:20:41', '2025-03-19 13:20:29', '177.39.209.43', NULL, NULL),
(42, '8f65faa7b6f0fbd14f7fa01550190eb0', 'Visitante', '', 'fechado', '2025-03-19 13:23:28', '2025-03-19 13:24:22', '2025-03-19 13:23:31', '177.39.209.43', NULL, NULL),
(43, '1c60c2b19d40398b1a89a53703562cfe', 'Visitante', '', 'fechado', '2025-03-19 13:26:15', '2025-03-19 13:26:43', '2025-03-19 13:26:17', '177.39.209.43', NULL, NULL),
(44, '006e6f42b109c1d28f6aff6c8a198c04', 'Visitante', '', 'fechado', '2025-04-08 15:47:18', '2025-04-08 15:47:37', '2025-04-08 15:47:24', '177.39.209.43', NULL, NULL),
(45, 'cdd2e5e63f9f2fbff7782852f23c21ca', 'Visitante', '', 'fechado', '2025-04-14 01:26:30', '2025-04-14 01:59:46', '2025-04-14 01:59:30', '45.167.151.40', NULL, NULL),
(46, '70cac86cae3832be7257e7b35f97bb03', 'Visitante', '', 'fechado', '2025-05-12 18:20:10', '2025-05-13 18:25:13', '2025-05-12 18:20:10', '177.39.213.200', NULL, NULL),
(47, '5e9046e7764ce9b6cefce0d5aa3ae1c5', 'Visitante', '', 'fechado', '2025-06-12 23:19:45', '2025-06-13 17:13:06', '2025-06-12 23:19:45', '131.100.11.217', NULL, NULL),
(48, '81da2b6f5e7dc78127cc58b111692fd6', 'Visitante', '', 'fechado', '2025-07-24 16:53:47', '2025-07-24 17:02:52', '2025-07-24 17:02:47', '177.220.180.242', NULL, NULL),
(49, 'f7bccc67b45ebd7abf6cab6290a7f305', 'Visitante', '', 'fechado', '2025-07-24 18:39:14', '2025-07-24 18:40:42', '2025-07-24 18:39:20', '177.39.209.43', NULL, NULL),
(50, '14000425df9d0b2832a863b50bed7ab2', 'Visitante', '', 'fechado', '2025-07-24 18:39:56', '2025-07-24 18:40:39', '2025-07-24 18:40:01', '177.39.209.43', NULL, NULL),
(51, '11984d489b99ab3dff9b28d6ead573d1', 'Visitante', '', 'fechado', '2025-07-24 18:40:22', '2025-07-24 18:40:29', '2025-07-24 18:40:22', '177.39.209.43', NULL, NULL),
(52, 'bde19159fea74a8976abf34581b2ad00', 'Visitante', '', 'fechado', '2025-07-24 19:11:22', '2025-07-24 19:12:43', '2025-07-24 19:12:08', '177.39.209.43', NULL, NULL),
(53, 'c0399895125d648b3792f592c11eefc1', 'Visitante', '', 'fechado', '2025-07-24 19:12:28', '2025-07-24 19:12:38', '2025-07-24 19:12:28', '177.39.209.43', NULL, NULL),
(54, '4da94b0e7fa4058fea2e2065a25dd3b0', 'Visitante', '', 'fechado', '2025-07-24 19:19:02', '2025-07-24 19:20:10', '2025-07-24 19:19:42', '177.39.209.43', NULL, NULL),
(55, 'b64b16f48b7b6c795dd7c3d3ccbfec99', 'Visitante', '', 'fechado', '2025-07-24 19:19:59', '2025-07-24 19:20:13', '2025-07-24 19:19:59', '177.39.209.43', NULL, NULL),
(56, 'bea2f1797085a82dd52acfd0d360de17', 'Visitante', '', 'fechado', '2025-07-24 20:03:46', '2025-07-24 20:04:34', '2025-07-24 20:04:04', '177.39.209.43', NULL, NULL),
(57, '645cc877144258bf8e2ad67f3263006c', 'Visitante', '', 'fechado', '2025-07-24 20:04:27', '2025-07-24 20:10:37', '2025-07-24 20:10:00', '177.39.209.43', NULL, NULL),
(58, '945bd7ada10d01a386a0ed78abd2ec93', 'Visitante', '', 'fechado', '2025-07-24 20:10:31', '2025-07-24 20:15:57', '2025-07-24 20:15:04', '177.39.209.43', NULL, NULL),
(59, '8b023806fdd4222b64a64766ea2862ad', 'Visitante', '', 'fechado', '2025-07-24 20:15:52', '2025-07-24 20:19:47', '2025-07-24 20:18:48', '177.39.209.43', NULL, NULL),
(60, '4de1b37baf9a0ce493413348b6d3a636', 'Visitante', '', 'fechado', '2025-07-24 20:19:37', '2025-07-24 20:22:56', '2025-07-24 20:22:09', '177.39.209.43', NULL, NULL),
(61, 'b6313050e884a05dff0d3d8ee2d35a50', 'Visitante', '', 'fechado', '2025-07-24 20:22:47', '2025-07-24 20:30:11', '2025-07-24 20:29:13', '177.39.209.43', NULL, NULL),
(62, '85f9535ee208b4d6adb9146773942e4b', 'Visitante', '', 'fechado', '2025-07-24 20:28:15', '2025-07-24 20:30:14', '2025-07-24 20:28:29', '177.39.209.43', NULL, NULL),
(63, '6469a3309f484c6148c52860a5efd4f3', 'Visitante', '', 'fechado', '2025-08-01 18:24:46', '2025-08-01 19:15:21', '2025-08-01 19:01:30', '168.196.151.84', NULL, NULL),
(64, 'a1823da15f0e4cb6583b9540159e8a10', 'Visitante', '', 'fechado', '2025-08-02 21:15:10', '2025-08-04 12:10:28', '2025-08-04 12:08:46', '45.167.151.158', NULL, NULL),
(65, 'eb1faa7aad7cae407142f0c50d43f087', 'Visitante', '', 'fechado', '2025-08-24 00:04:25', '2025-08-24 00:05:24', '2025-08-24 00:04:28', '45.167.151.167', NULL, NULL),
(66, 'd548ae3d4b6259859d1a1e1d83ba0df9', 'Visitante', '', 'fechado', '2025-08-24 00:05:37', '2025-08-24 00:06:24', '2025-08-24 00:06:12', '45.167.151.167', NULL, NULL),
(67, 'c64408f17d94dab524b9c373045a37ef', 'Visitante', '', 'fechado', '2025-10-21 18:18:37', '2025-10-21 18:19:54', '2025-10-21 18:19:14', '177.39.213.200', NULL, NULL),
(68, 'd6c8ec91aaa0a5243ce100ad5a21aa1d', 'Visitante', '', 'fechado', '2025-10-24 11:42:41', '2025-10-24 12:34:25', '2025-10-24 12:32:28', '177.39.209.43', NULL, NULL),
(69, '880de4d5c6bf8d025651780316797385', 'Valdecir', '', 'fechado', '2025-10-24 11:50:18', '2025-10-24 11:50:51', '2025-10-24 11:50:25', '177.39.209.43', NULL, NULL),
(70, '9b102b3a9abf8d6b012da7063976ff3e', 'Administrador', 'admin@admin.com', 'fechado', '2025-10-24 12:33:52', '2025-10-24 12:34:43', '2025-10-24 12:33:56', '177.39.209.43', NULL, NULL),
(71, 'be3a38f5110c5ffebba346e0cdc34b17', 'Valdecir dos Santos', '', 'fechado', '2025-11-13 17:09:20', '2025-11-13 17:09:47', '2025-11-13 17:09:25', '177.39.209.43', NULL, NULL),
(72, 'dcff239206446b77eefb1623da1a1aa1', 'Visitante', '', 'fechado', '2025-11-13 17:35:29', '2025-11-14 13:49:32', '2025-11-14 13:49:32', '181.77.213.59', NULL, NULL),
(73, '598fe96921afe8282e165e8586e0164f', 'Valdecir', '', 'fechado', '2025-11-18 02:19:09', '2025-11-18 02:22:03', '2025-11-18 02:19:15', '45.167.151.211', NULL, NULL),
(74, '579c28c1fe496a68804692a74cf70f5a', 'Visitante', '', 'fechado', '2025-12-14 19:19:19', '2025-12-17 14:25:30', '2025-12-14 19:19:25', '2804:1184:4013:2601:6121:7743:4c5b:1530', NULL, NULL),
(75, '17559662e63761ae67a5b0d992aa16b3', 'Visitante', '', 'fechado', '2025-12-23 03:51:57', '2025-12-26 22:18:18', '2025-12-23 03:51:57', '2804:1184:4013:2601:adb4:ab8b:7ef2:9aa7', NULL, NULL),
(76, '2646c48f702ed1277526439ab0acd823', 'Teste', '', 'fechado', '2026-01-02 20:19:38', '2026-01-02 21:06:38', '2026-01-02 21:06:16', '2804:1184:4013:2601:bd4:d5dd:4610:dcee', '863', '668'),
(77, 'f62980ad54c97614bbf080e0f0ed239c', 'Val', '', 'fechado', '2026-01-02 20:38:14', '2026-01-02 20:43:38', '2026-01-02 20:38:16', '2804:1184:4013:2601:bd4:d5dd:4610:dcee', NULL, NULL),
(78, 'a8afba6f1983cdbe2050c63f61677120', 'Val', '', 'fechado', '2026-01-02 20:41:36', '2026-01-02 20:43:40', '2026-01-02 20:41:52', '2804:1184:4013:2601:bd4:d5dd:4610:dcee', '861', '666'),
(79, '251c0f4cd288f79191fdde57573de4dd', 'Jaime', '', 'fechado', '2026-01-02 20:45:28', '2026-01-02 20:51:56', '2026-01-02 20:51:35', '2804:1184:4013:2601:bd4:d5dd:4610:dcee', '862', '667'),
(80, 'de3dc6c0887cba82421b35043ff4a840', 'Administrador', 'admin@admin.com', 'fechado', '2026-01-06 16:54:50', '2026-01-07 19:53:07', '2026-01-06 16:54:57', '2804:2064:108:3200:1df9:624a:50d1:af34', '870', '684'),
(81, '0fe063b205d445cbabf973860bd69457', 'Valdecir', '', 'fechado', '2026-01-08 13:28:38', '2026-01-10 03:30:32', '2026-01-08 13:28:45', '177.39.209.43', '875', '700'),
(82, '0d3d6c6ea16b2747a4f83ab90c966756', 'Aline', '', 'fechado', '2026-01-10 04:40:07', '2026-01-11 17:06:16', '2026-01-10 04:53:21', '2804:1184:4013:2601:71dc:2da3:5def:c1f7', '880', '715'),
(83, '82abe2236e7d651a41b67efc54d7a4c9', 'Cliente', '', 'fechado', '2026-01-10 04:53:44', '2026-01-11 17:06:13', '2026-01-10 04:53:49', '2804:1184:4013:2601:71dc:2da3:5def:c1f7', '881', '716'),
(84, '241646f35c1fbf51d7aa7340fda8f1cb', 'Administrador', 'admin@admin.com', 'fechado', '2026-01-11 20:48:17', '2026-01-11 20:50:33', '2026-01-11 20:49:26', '2804:1184:4013:2601:bd4:d5dd:4610:dcee', '870', '723'),
(85, '409c8c8840b77eac2104c3879bb161b8', 'Visitante', '', 'fechado', '2026-01-11 20:48:42', '2026-01-11 20:50:47', '2026-01-11 20:50:47', '2804:1184:4013:2601:bd4:d5dd:4610:dcee', '882', '724'),
(86, '691040dd70d3168817529df6d8b7481d', 'Visitante', '', 'fechado', '2026-01-11 20:52:32', '2026-01-11 21:00:34', '2026-01-11 21:00:22', '2804:1184:4013:2601:bd4:d5dd:4610:dcee', '883', '725'),
(87, '4ae35247574ede3b4a24621181056d23', 'olá', '', 'fechado', '2026-01-12 11:41:18', '2026-01-13 01:20:32', '2026-01-12 11:41:22', '177.39.209.43', '885', '731'),
(88, 'a60158689c527a7f663ad3b005cf87c7', 'Administrador', 'admin@admin.com', 'ativo', '2026-01-16 18:30:12', '2026-01-18 17:52:04', '2026-01-18 17:52:04', '2804:1184:4013:2601:3525:d42f:d1da:d97f', '870', '757');

-- --------------------------------------------------------

--
-- Estrutura para tabela `contacts`
--

CREATE TABLE `contacts` (
  `id` int(11) NOT NULL,
  `number` varchar(20) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `avatar_updated_at` datetime DEFAULT NULL,
  `tags` text DEFAULT NULL,
  `sector` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `last_message` text DEFAULT NULL,
  `last_message_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `unread_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `contact_notes`
--

CREATE TABLE `contact_notes` (
  `id` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `contact_tags`
--

CREATE TABLE `contact_tags` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `color` varchar(7) DEFAULT '#6c757d',
  `sector` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `contact_tags`
--

INSERT INTO `contact_tags` (`id`, `name`, `color`, `sector`, `created_at`) VALUES
(1, 'Cliente VIP', '#dc3545', 'Geral', '2025-07-06 20:29:14'),
(2, 'Problema', '#fd7e14', 'Geral', '2025-07-06 20:29:14'),
(3, 'Dúvida', '#20c997', 'Geral', '2025-07-06 20:29:14'),
(4, 'Orçamento', '#0d6efd', 'Geral', '2025-07-06 20:29:14'),
(5, 'Reclamação', '#dc3545', 'Geral', '2025-07-06 20:29:14'),
(6, 'Elogio', '#198754', 'Geral', '2025-07-06 20:29:14'),
(7, 'Urgente', '#dc3545', 'Geral', '2025-07-06 20:29:14'),
(8, 'Seguimento', '#6f42c1', 'Geral', '2025-07-06 20:29:14'),
(9, 'Farmacia Popular', '#0088ff', 'Geral', '2025-07-10 11:10:49'),
(10, 'Promoção', '#780099', 'Geral', '2025-07-17 23:11:01'),
(11, 'Projeto', '#6c757d', 'Geral', '2025-07-17 23:11:13');

-- --------------------------------------------------------

--
-- Estrutura para tabela `contact_tag_relations`
--

CREATE TABLE `contact_tag_relations` (
  `id` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `contatos`
--

CREATE TABLE `contatos` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `assunto` varchar(100) NOT NULL,
  `mensagem` text NOT NULL,
  `status` enum('novo','em_andamento','respondido','arquivado') NOT NULL DEFAULT 'novo',
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `notas_admin` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `email_campanhas`
--

CREATE TABLE `email_campanhas` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `assunto` varchar(255) NOT NULL,
  `conteudo` text DEFAULT NULL,
  `data_criacao` datetime DEFAULT current_timestamp(),
  `status` tinyint(1) DEFAULT 1,
  `data_envio` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `farmacias`
--

CREATE TABLE `farmacias` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `whatsapp` varchar(20) DEFAULT NULL,
  `qr_code_token` varchar(64) DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `valor_entrega_gratis` decimal(10,2) DEFAULT NULL,
  `taxa_entrega` decimal(10,2) DEFAULT NULL,
  `usar_evolution_api` tinyint(1) DEFAULT 0,
  `evolution_instance_name` varchar(100) DEFAULT NULL,
  `evolution_api_key` varchar(255) DEFAULT NULL,
  `evolution_api_url` varchar(255) DEFAULT 'https://evolution.probotfarmapro.online',
  `pixel_id` varchar(32) DEFAULT NULL,
  `pixel_ativo` tinyint(1) DEFAULT 0,
  `fb_pixel_id` varchar(50) DEFAULT NULL,
  `fb_access_token` text DEFAULT NULL,
  `cor_primaria` varchar(7) DEFAULT '#7B68EE',
  `cor_secundaria` varchar(7) DEFAULT '#6A5ACD',
  `logo_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `farmacias`
--

INSERT INTO `farmacias` (`id`, `usuario_id`, `nome`, `endereco`, `telefone`, `whatsapp`, `qr_code_token`, `data_criacao`, `valor_entrega_gratis`, `taxa_entrega`, `usar_evolution_api`, `evolution_instance_name`, `evolution_api_key`, `evolution_api_url`, `pixel_id`, `pixel_ativo`, `fb_pixel_id`, `fb_access_token`, `cor_primaria`, `cor_secundaria`, `logo_url`) VALUES
(1, 1, 'Minha Farmácia Teste', 'Rua Celso Sílvio Gralha', '44998966548', '44998966548', 'daab33913ff2670bc68a224bf73dae8b', '2025-04-07 02:25:09', 50.00, 8.00, 0, NULL, NULL, 'https://evolution.probotfarmapro.online', 'FP-1E19E8A8149B', 1, NULL, NULL, '#7B68EE', '#6A5ACD', NULL),
(3, 10, 'Farmacia do Gege', 'Rua 1 de Maio', '44999157604', '44999157604', '11cdab6d7454a72c686b5b2c88eb4b0b', '2025-04-07 14:01:09', 50.00, 3.00, 0, NULL, NULL, 'https://evolution.probotfarmapro.online', 'FP9449C16E84313827', 0, NULL, NULL, '#7B68EE', '#6A5ACD', NULL),
(9, 23, 'Minha Farmácia', NULL, NULL, NULL, 'a66d2710f1534b555442b8f50ec27287', '2025-04-09 19:12:03', NULL, NULL, 0, NULL, NULL, 'https://evolution.probotfarmapro.online', 'FP1022816E888960EC', 0, NULL, NULL, '#7B68EE', '#6A5ACD', NULL),
(10, 51, 'Minha Farmácia', NULL, NULL, NULL, '6cb0fe55346aac54da6aa5c0702fa18d', '2025-04-10 18:11:10', NULL, NULL, 0, NULL, NULL, 'https://evolution.probotfarmapro.online', 'FPACD48B07B006ED8C', 0, NULL, NULL, '#7B68EE', '#6A5ACD', NULL),
(11, 32, 'Minha Farmácia', NULL, NULL, NULL, '23ccfa8c3bbb0ff1a2ac12509c3c0027', '2025-04-17 01:23:05', NULL, NULL, 0, NULL, NULL, 'https://evolution.probotfarmapro.online', 'FPBA57611B425A3366', 0, NULL, NULL, '#7B68EE', '#6A5ACD', NULL),
(12, 30, 'Minha Farmácia', NULL, NULL, NULL, 'd63736c806a2f4d1f02c1a7067c39f8e', '2025-04-17 10:14:38', NULL, NULL, 0, NULL, NULL, 'https://evolution.probotfarmapro.online', 'FPD76EC02B0D0F5D2B', 0, NULL, NULL, '#7B68EE', '#6A5ACD', NULL),
(13, 60, 'Minha Farmácia', NULL, NULL, NULL, '52316b1c1927b5609e9930653fec0b05', '2025-06-09 14:26:11', NULL, NULL, 0, NULL, NULL, 'https://evolution.probotfarmapro.online', 'FP76111365736391D6', 0, NULL, NULL, '#7B68EE', '#6A5ACD', NULL),
(14, 41, 'Farmácia Rondofarma', 'Avenida Brasil 1583', '4498604226', '44998604226', 'c58077159fe93b7afc233001e2caa0d2', '2025-07-07 13:13:45', 50.00, 4.00, 0, NULL, NULL, 'https://evolution.probotfarmapro.online', 'FP3BDCF7EB1B6B75C1', 0, NULL, NULL, '#7B68EE', '#6A5ACD', NULL),
(16, 47, 'Minha Farmácia', NULL, NULL, NULL, 'ba319655a0daa3e129fd861803c18c60', '2025-07-10 14:31:05', NULL, NULL, 0, NULL, NULL, 'https://evolution.probotfarmapro.online', 'FP11CF6F7D79FEEBD1', 0, NULL, NULL, '#7B68EE', '#6A5ACD', NULL),
(17, 15, 'FARMACIA MEDPREV', 'AV JOÃO MARANGONI,483', '4430481222', '44998769257', 'bee58bb231ce4cf58e3b4354ba3aa4a0', '2025-07-11 14:41:18', 30.00, 7.00, 0, NULL, NULL, 'https://evolution.probotfarmapro.online', 'FP73EE33E1118CCCD5', 0, NULL, NULL, '#7B68EE', '#6A5ACD', NULL),
(18, 50, 'Minha Farmácia', NULL, NULL, NULL, 'e0dcc9dc21959be8d116d16a5d701acb', '2025-07-25 18:49:42', NULL, NULL, 0, NULL, NULL, 'https://evolution.probotfarmapro.online', 'FPD791CCA1C8C5114F', 0, NULL, NULL, '#7B68EE', '#6A5ACD', NULL),
(19, 17, 'Minha Farmácia', NULL, NULL, NULL, '08322849cb37fbf754fd7c531ecfa27d', '2025-07-26 13:44:32', NULL, NULL, 0, NULL, NULL, 'https://evolution.probotfarmapro.online', 'FP9AC884A1CAEC8E42', 0, NULL, NULL, '#7B68EE', '#6A5ACD', NULL),
(20, 72, 'Minha Farmácia', NULL, NULL, NULL, '43fd788d02e906dcddef25fd87f468e4', '2025-08-01 18:43:46', NULL, NULL, 0, NULL, NULL, 'https://evolution.probotfarmapro.online', 'FP6F541DAC926C80A2', 0, NULL, NULL, '#7B68EE', '#6A5ACD', NULL),
(21, 54, 'Minha Farmácia', NULL, NULL, NULL, 'd2b3f2fb3830db8102b478def28cadd1', '2025-08-04 12:15:29', NULL, NULL, 0, NULL, NULL, 'https://evolution.probotfarmapro.online', 'FP5AD08F85018016B1', 0, NULL, NULL, '#7B68EE', '#6A5ACD', NULL),
(22, 75, 'Minha Farmácia', NULL, NULL, NULL, '4d3522554b2f69c4fd285e041c5e29f9', '2025-08-21 17:10:05', NULL, NULL, 0, NULL, NULL, 'https://evolution.probotfarmapro.online', 'FPEE570311C200A2BE', 0, NULL, NULL, '#7B68EE', '#6A5ACD', NULL),
(24, 35, 'Minha Farmácia', NULL, NULL, NULL, 'ba92693bb814b3b2555adefd7ed3b901', '2025-09-09 12:50:14', NULL, NULL, 0, NULL, NULL, 'https://evolution.probotfarmapro.online', 'FP46C89E41B16E8A53', 0, NULL, NULL, '#7B68EE', '#6A5ACD', NULL),
(25, 40, 'Minha Farmácia', NULL, NULL, NULL, 'f4302a02e8b263dc325cb450b344540c', '2025-10-24 12:45:58', NULL, NULL, 0, NULL, NULL, 'https://evolution.probotfarmapro.online', 'FP0E02A859236B4204', 0, NULL, NULL, '#7B68EE', '#6A5ACD', NULL),
(26, 83, 'Minha Farmácia', NULL, NULL, NULL, '68401503d794a3db558d5d1b05f3a7a3', '2025-10-30 18:33:38', NULL, NULL, 0, NULL, NULL, 'https://evolution.probotfarmapro.online', 'FPB41EF0592D760F44', 0, NULL, NULL, '#7B68EE', '#6A5ACD', NULL),
(27, 82, 'Minha Farmácia', NULL, NULL, NULL, '1dbe56109a3c9200e4487990495d1ce0', '2025-11-04 11:40:29', NULL, NULL, 0, NULL, NULL, 'https://evolution.probotfarmapro.online', 'FP39D5F43CBD182730', 0, NULL, NULL, '#7B68EE', '#6A5ACD', NULL),
(30, 92, 'Minha Farmácia', NULL, NULL, NULL, '8be4dbecebfadeb31b2c430bb6f42d3d', '2026-01-12 13:59:06', NULL, NULL, 0, NULL, NULL, 'https://evolution.probotfarmapro.online', NULL, 0, NULL, NULL, '#7B68EE', '#6A5ACD', NULL),
(31, 94, 'Minha Farmácia', NULL, NULL, NULL, '46272d8a1868d906df9290212de0073e', '2026-01-13 11:25:10', NULL, NULL, 0, NULL, NULL, 'https://evolution.probotfarmapro.online', NULL, 0, NULL, NULL, '#7B68EE', '#6A5ACD', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `feedbacks`
--

CREATE TABLE `feedbacks` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo_feedback` enum('sugestao_ferramenta','melhoria','bug','elogio','outro') NOT NULL,
  `mensagem` text NOT NULL,
  `ferramentas_desejadas` text DEFAULT NULL,
  `nota_satisfacao` tinyint(4) DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `lido` tinyint(1) DEFAULT 0,
  `respondido` tinyint(1) DEFAULT 0,
  `resposta` text DEFAULT NULL,
  `data_resposta` timestamp NULL DEFAULT NULL,
  `respondido_por` int(11) DEFAULT NULL,
  `status` enum('pendente','respondido','arquivado') DEFAULT 'pendente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `feedbacks`
--

INSERT INTO `feedbacks` (`id`, `usuario_id`, `tipo_feedback`, `mensagem`, `ferramentas_desejadas`, `nota_satisfacao`, `data_criacao`, `lido`, `respondido`, `resposta`, `data_resposta`, `respondido_por`, `status`) VALUES
(1, 1, 'sugestao_ferramenta', 'Eu gostaria de fazer anuncios, mas gentor é caro', 'Eu gostaria de uma ferramenta que crie anuncios no instagram', 3, '2025-12-14 18:39:36', 1, 0, NULL, NULL, NULL, 'pendente'),
(2, 23, 'melhoria', 'Ferramenta é de muita qualidade e fácil de operar, uma sugestão é os temas de ofertas chegarem um pouco mais cedo das datas sazonais.', 'Seria ótimo uma integração com whatsapp busines e istagram', 4, '2025-12-18 19:28:41', 1, 1, 'Olá, Viver Mais!\r\n\r\nObrigado pelo feedback! 🙌\r\n\r\n✅ TEMAS SAZONAIS\r\nÓtima sugestão! A partir de 2025, disponibilizaremos todos os temas com 30+ dias de antecedência.\r\n\r\n✅ INTEGRAÇÃO WHATSAPP/INSTAGRAM\r\nEntendemos a necessidade! Porém, esse tipo de integração enfrenta limitações técnicas importantes:\r\n\r\n- WhatsApp Business e Instagram APIs exigem aplicativos individuais por farmácia\r\n- Cada cliente precisaria de login e credenciais próprias (não funciona como SaaS)\r\n- Aprovações do Meta que levam semanas por conta\r\n- Custos por mensagem e manutenção contínua de tokens\r\n\r\nPor isso, ferramentas profissionais (Canva, Adobe Express) usam o fluxo:\r\nDesktop (criação) → Download → Postagem manual via web/app\r\n\r\nIsso garante qualidade na criação (tela grande) e controle total na publicação!\r\n\r\nAbraços!\r\nEquipe FarmaPro', '2025-12-19 14:03:29', NULL, 'respondido');

-- --------------------------------------------------------

--
-- Estrutura para tabela `feedback_details`
--

CREATE TABLE `feedback_details` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `feedback_type` enum('positive','negative','suggestion') NOT NULL,
  `feedback_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `keywords`
--

CREATE TABLE `keywords` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `keyword` varchar(100) NOT NULL,
  `weight` decimal(5,2) NOT NULL DEFAULT 1.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `session_id` int(11) DEFAULT NULL,
  `from_number` varchar(20) DEFAULT NULL,
  `contact_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `type` enum('text','image','audio','video','document','location','contact','sticker') DEFAULT 'text',
  `media_url` varchar(255) DEFAULT NULL,
  `is_from_me` tinyint(1) DEFAULT 0,
  `status` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pedidos_whatsapp`
--

CREATE TABLE `pedidos_whatsapp` (
  `id` int(11) NOT NULL,
  `farmacia_id` int(11) NOT NULL,
  `cliente_nome` varchar(100) NOT NULL,
  `cliente_telefone` varchar(20) NOT NULL,
  `endereco_entrega` text NOT NULL,
  `itens_pedido` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`itens_pedido`)),
  `valor_total` decimal(10,2) NOT NULL,
  `valor_entrega` decimal(10,2) DEFAULT 0.00,
  `forma_pagamento` varchar(50) NOT NULL,
  `troco_para` decimal(10,2) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `status_pedido` enum('pendente','confirmado','preparando','enviado','entregue','cancelado') DEFAULT 'pendente',
  `data_criacao` timestamp NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `permissoes_pedidos`
--

CREATE TABLE `permissoes_pedidos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `has_panel_access` tinyint(1) DEFAULT 0,
  `is_admin_pedidos` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pixel_eventos`
--

CREATE TABLE `pixel_eventos` (
  `id` bigint(20) NOT NULL,
  `farmacia_id` int(11) NOT NULL,
  `pixel_id` varchar(32) NOT NULL,
  `event_id` varchar(64) NOT NULL,
  `event_name` enum('PageView','ViewContent','AddToCart','InitiateCheckout','Contact','Purchase','Lead') NOT NULL,
  `event_time` timestamp NULL DEFAULT current_timestamp(),
  `user_agent` text DEFAULT NULL,
  `ip_hash` varchar(64) DEFAULT NULL,
  `fbc` varchar(255) DEFAULT NULL,
  `fbp` varchar(255) DEFAULT NULL,
  `external_id_hash` varchar(64) DEFAULT NULL,
  `content_id` varchar(100) DEFAULT NULL,
  `content_name` varchar(255) DEFAULT NULL,
  `content_category` varchar(100) DEFAULT NULL,
  `value` decimal(10,2) DEFAULT 0.00,
  `currency` varchar(3) DEFAULT 'BRL',
  `num_items` int(11) DEFAULT 0,
  `source_url` text DEFAULT NULL,
  `referrer` text DEFAULT NULL,
  `enviado_facebook` tinyint(1) DEFAULT 0,
  `enviado_em` timestamp NULL DEFAULT NULL,
  `facebook_response` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `polls`
--

CREATE TABLE `polls` (
  `id` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `question` text NOT NULL,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`options`)),
  `poll_type` enum('single','multiple') DEFAULT 'single',
  `message_id` varchar(100) DEFAULT NULL,
  `status` enum('active','closed','expired') DEFAULT 'active',
  `expires_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `poll_responses`
--

CREATE TABLE `poll_responses` (
  `id` int(11) NOT NULL,
  `poll_id` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL,
  `selected_options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`selected_options`)),
  `response_text` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `produtos`
--

CREATE TABLE `produtos` (
  `id` int(11) NOT NULL,
  `farmacia_id` int(11) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `principio_ativo` varchar(255) DEFAULT NULL,
  `registro_ms` varchar(50) DEFAULT NULL,
  `indicacao` text DEFAULT NULL,
  `contra_indicacao` text DEFAULT NULL,
  `tarja` enum('sem_tarja','amarela','vermelha','preta') DEFAULT 'sem_tarja',
  `exige_receita` tinyint(1) DEFAULT 0,
  `mostrar_no_cardapio` tinyint(1) DEFAULT 1,
  `preco` decimal(10,2) NOT NULL,
  `imagem` varchar(255) DEFAULT NULL,
  `promocao` tinyint(1) DEFAULT 0,
  `destaque` tinyint(1) DEFAULT 0,
  `estoque_disponivel` tinyint(1) DEFAULT 1,
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_leite` tinyint(1) DEFAULT 0,
  `tem_tamanhos` tinyint(1) DEFAULT 0,
  `ean` varchar(20) DEFAULT NULL,
  `sku_externo` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `produtos`
--

INSERT INTO `produtos` (`id`, `farmacia_id`, `categoria_id`, `nome`, `descricao`, `principio_ativo`, `registro_ms`, `indicacao`, `contra_indicacao`, `tarja`, `exige_receita`, `mostrar_no_cardapio`, `preco`, `imagem`, `promocao`, `destaque`, `estoque_disponivel`, `data_atualizacao`, `is_leite`, `tem_tamanhos`) VALUES
(9, 14, 7, 'Shampoo Johnsons', '', '', NULL, '', '', 'sem_tarja', 0, 1, 20.00, NULL, 0, 0, 1, '2025-07-07 13:17:16', 0, 0),
(12, 17, 7, 'FRALDA CAPRICHO ', 'P-80 G-60\r\nM-70 XG-50 XXG-40 (sujeito a disponibilidade de estoque)', '', NULL, '', '', 'sem_tarja', 0, 1, 41.99, 'uploads/1752320091_Untitled design.png', 0, 0, 1, '2025-08-16 18:47:41', 0, 1),
(16, 17, 7, 'FRALDA MILI LOVE CARE ', 'P-28 M-26 G-24\r\nXG-22 XXG-20 XXXG-18 (sujeito a disponibilidade de estoque)', '', NULL, '', '', 'sem_tarja', 0, 1, 25.99, 'uploads/1752321114_Untitled design (1).png', 0, 0, 1, '2025-08-16 18:48:36', 0, 1),
(17, 17, 7, 'FRALDA BABYSEC (MEGA)', 'P-42 M-38 G-32\r\nXG-30 XXG -28 (sujeito a disponibilidade de estoque)', '', NULL, '', '', 'sem_tarja', 0, 1, 37.99, 'uploads/1752324973_fralda_infantil_babysec_ultra_galinha_pintadinha_mega_563_1_6dcc7535c9e4a979f46139bb8d768cd7.webp', 0, 0, 1, '2025-08-16 18:47:25', 0, 1),
(18, 17, 7, 'FRALDA BABYSEC (HYPER)', 'M-68 G-60 XG-56\r\nXXG-48 (sujeito a disponibilidade de estoque)', '', NULL, '', '', 'sem_tarja', 0, 1, 67.99, 'uploads/1752329306_D_NQ_NP_722736-MLB84612469231_052025-O-fralda-inf-baby-sec-hiper.webp', 0, 0, 1, '2025-08-16 18:47:08', 0, 1),
(19, 3, 6, 'Leite Ninho Fases', '', '', NULL, '', '', 'sem_tarja', 0, 1, 29.50, 'uploads/1752698240_download (1).png', 0, 1, 0, '2026-01-06 12:49:25', 0, 0),
(20, 17, 7, 'FRALDA MILI GIGA ', 'M-88 G- 78\r\nXG-68 XXG-58 (sujeito a disponibilidade de estoque)', '', NULL, '', '', 'sem_tarja', 0, 1, 67.99, 'uploads/1753358949_fralda-mili-baby-giga.webp', 0, 0, 1, '2025-08-16 18:48:21', 0, 1),
(21, 17, 7, 'FRALDA HUGGIES SUPREME', 'P-48 M-66 G-58\r\nXG-56 XXG-54 (sujeito a disponibilidade de estoque)', '', NULL, '', '', 'sem_tarja', 0, 1, 82.99, 'uploads/1753360181_7896007552825.png', 0, 0, 1, '2025-08-16 18:35:55', 0, 0),
(22, 17, 7, 'FRALDA PERSONALIDADE BABYTOTAL CARE', 'M-60 G-54\r\nXG-48 XXG-42 (sujeito a disponibilidade de estoque)', '', NULL, '', '', 'sem_tarja', 0, 1, 57.99, 'uploads/1753361444_7896007552825 (1).png', 0, 0, 1, '2025-08-16 18:48:56', 0, 1),
(23, 17, 7, 'FRALDA LOVE CARE PANTS CALCINHA', 'M-36 G-30\r\nXG-24 XXG-24 (sujeito a disponibilidade de estoque)', '', NULL, '', '', 'sem_tarja', 0, 1, 41.99, 'uploads/1753361709_Mili-lanca-edicao-especial-da-fralda-Love-Care-Pants-com-filhos-de-colaboradores-nas-embalagens (1).jpg', 0, 0, 1, '2025-08-27 14:04:39', 0, 1),
(24, 17, 7, 'FRALDA PERSONALIDADE PLUS ECONOMICA', 'P-60 M-52 G-46\r\nXG-40 XXG-34 (sujeito a disponibilidade de estoque)', '', NULL, '', '', 'sem_tarja', 0, 1, 38.99, 'uploads/1753363923_Design sem nome.png', 0, 0, 1, '2025-08-16 18:49:20', 0, 1),
(25, 17, 7, 'TOALHA UMED BABY FREE C/50 3 UNIDADES', 'KIT C/3 TOALHAS POR 10,00', '', NULL, '', '', 'sem_tarja', 0, 1, 10.00, 'uploads/1753364315_Design sem nome (1).png', 0, 0, 1, '2025-07-24 13:38:35', 0, 0),
(26, 17, 7, 'TOALHA UMED BABYSTAR C/100', '(sujeito a disponibilidade de estoque)', '', NULL, '', '', 'sem_tarja', 0, 1, 4.99, 'uploads/1753364395_download (22).jpg', 0, 0, 1, '2025-08-16 18:49:50', 0, 0),
(27, 17, 7, 'TOALHA UMED TROPOLINO C/100', 'OTIMA QUALIDADE (sujeito a disponibilidade de estoque)', '', NULL, '', '', 'sem_tarja', 0, 1, 9.99, 'uploads/1753364498_download (23).jpg', 0, 0, 1, '2025-08-16 18:50:41', 0, 0),
(28, 17, 7, 'TOALHA UMED MILI C/100', 'EXCELENTE QUALIDADE (sujeito a disponibilidade de estoque)', '', NULL, '', '', 'sem_tarja', 0, 1, 12.99, 'uploads/1753364677_download (24).jpg', 0, 0, 1, '2025-08-16 18:50:30', 0, 0),
(29, 17, 7, 'TOALHA JOHNSONS C/48 RECEM NASCIDO', 'AGUA E ALGODAO', '', NULL, '', '', 'sem_tarja', 0, 1, 21.99, 'uploads/1753365291_Design sem nome (2).png', 0, 0, 1, '2025-07-24 13:54:51', 0, 0),
(30, 17, 7, 'TOALHA FISHER-PRICE C/50', '(sujeito a disponibilidade de estoque)', '', NULL, '', '', 'sem_tarja', 0, 1, 9.99, 'uploads/1753365424_download (27).jpg', 0, 0, 1, '2025-08-16 18:50:18', 0, 0),
(31, 17, 7, 'TOALHA HUGGIES C/48', '(sujeito a disponibilidade de estoque)', '', NULL, '', '', 'sem_tarja', 0, 1, 15.99, 'uploads/1753365495_download (28).jpg', 0, 0, 1, '2025-08-16 18:49:59', 0, 0),
(39, 1, 1, 'Maxalgina Dipirona Sódica 500mg/ml Solução Gotas 20ml', '', 'Sua fórmula é composta por dipirona monoidratada, um analgésico e antitérmico eficaz que começa a agir entre 30 a 60 minutos após a administração, com efeito que dura cerca de 4 horas.', '1384100020025', 'Maxalgina Dipirona Sódica 500mg/ml Solução Gotas 20ml é um medicamento de uso oral indicado para o alívio da dor e redução da febre. Sua fórmula é composta por dipirona monoidratada, um analgésico e antitérmico eficaz que começa a agir entre 30 a 60 minutos após a administração, com efeito que dura cerca de 4 horas.', 'Este medicamento é contraindicado para pacientes com alergia à dipirona ou a outros derivados da pirazolona, bem como para pacientes com histórico de agranulocitose ou distúrbios hematológicos. Não deve ser usado por mulheres grávidas, lactantes, crianças menores de 3 meses ou com peso inferior a 5 kg, salvo sob orientação médica.', 'sem_tarja', 0, 1, 14.50, 'uploads/1768587763_16103285.webp', 0, 0, 1, '2026-01-19 14:07:12', 0, 0),
(40, 1, 7, 'Fralda Huggies Máxima Proteção', 'A Fralda Descartável Huggies Máxima Proteção Tamanho XXG, com 80 unidades, foi feita para acompanhar cada movimento do seu bebê com conforto e segurança. Supreme Care agora é Máxima Proteção: com absorção 5X mais rápida* e tecnologia Xtra-Flex, ela se adapta ao corpinho do bebê e distribui o xixi de forma rápida e uniforme. Assim, ajuda a evitar fralda caída mesmo quando cheia. Seu ajuste anatômico mantém a fralda no lugar, permitindo que o bebê brinque, se mova e explore com liberdade. Tudo isso com máxima proteção e até 12h/ proteção anti vazamentos** *Comparação feita com fraldas convencionais sem canais de absorção no segmento de fraldas que são usualmente vendidas na mesma faixa de preço **Desempenho da fralda pode variar com os hábitos, alimentação e características do bebê.', '', '', '', '', 'sem_tarja', 0, 1, 90.00, 'uploads/1768691704_processed-1768689212982.png', 1, 1, 1, '2026-01-17 23:15:48', 0, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `produto_tamanhos`
--

CREATE TABLE `produto_tamanhos` (
  `id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `tamanho_id` int(11) NOT NULL,
  `preco_adicional` decimal(10,2) DEFAULT 0.00,
  `estoque_disponivel` tinyint(1) DEFAULT 1,
  `data_criacao` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Despejando dados para a tabela `produto_tamanhos`
--

INSERT INTO `produto_tamanhos` (`id`, `produto_id`, `tamanho_id`, `preco_adicional`, `estoque_disponivel`, `data_criacao`) VALUES
(70, 18, 3, 0.00, 1, '2025-08-16 18:47:08'),
(71, 18, 4, 0.00, 1, '2025-08-16 18:47:08'),
(72, 18, 5, 0.00, 1, '2025-08-16 18:47:08'),
(73, 18, 6, 0.00, 1, '2025-08-16 18:47:08'),
(74, 17, 2, 0.00, 1, '2025-08-16 18:47:25'),
(75, 17, 3, 0.00, 1, '2025-08-16 18:47:25'),
(76, 17, 4, 0.00, 1, '2025-08-16 18:47:25'),
(77, 17, 5, 0.00, 1, '2025-08-16 18:47:25'),
(78, 17, 6, 0.00, 1, '2025-08-16 18:47:25'),
(79, 12, 2, 0.00, 1, '2025-08-16 18:47:41'),
(80, 12, 3, 0.00, 1, '2025-08-16 18:47:41'),
(81, 12, 4, 0.00, 1, '2025-08-16 18:47:41'),
(82, 12, 5, 0.00, 1, '2025-08-16 18:47:41'),
(83, 12, 6, 0.00, 1, '2025-08-16 18:47:41'),
(88, 20, 3, 0.00, 1, '2025-08-16 18:48:21'),
(89, 20, 4, 0.00, 1, '2025-08-16 18:48:21'),
(90, 20, 5, 0.00, 1, '2025-08-16 18:48:21'),
(91, 20, 6, 0.00, 1, '2025-08-16 18:48:21'),
(92, 16, 2, 0.00, 1, '2025-08-16 18:48:36'),
(93, 16, 3, 0.00, 1, '2025-08-16 18:48:36'),
(94, 16, 4, 0.00, 1, '2025-08-16 18:48:36'),
(95, 16, 5, 0.00, 1, '2025-08-16 18:48:36'),
(96, 16, 6, 0.00, 1, '2025-08-16 18:48:36'),
(97, 22, 3, 0.00, 1, '2025-08-16 18:48:56'),
(98, 22, 4, 0.00, 1, '2025-08-16 18:48:56'),
(99, 22, 5, 0.00, 1, '2025-08-16 18:48:56'),
(100, 22, 6, 0.00, 1, '2025-08-16 18:48:56'),
(101, 24, 2, 0.00, 1, '2025-08-16 18:49:20'),
(102, 24, 3, 0.00, 1, '2025-08-16 18:49:20'),
(103, 24, 4, 0.00, 1, '2025-08-16 18:49:20'),
(104, 24, 5, 0.00, 1, '2025-08-16 18:49:20'),
(105, 24, 6, 0.00, 1, '2025-08-16 18:49:20'),
(106, 23, 3, 0.00, 1, '2025-08-27 14:04:39'),
(107, 23, 4, 0.00, 1, '2025-08-27 14:04:39'),
(108, 23, 5, 0.00, 1, '2025-08-27 14:04:39'),
(109, 23, 6, 0.00, 1, '2025-08-27 14:04:39'),
(120, 40, 2, 2.00, 1, '2026-01-17 23:15:48'),
(121, 40, 3, 0.00, 1, '2026-01-17 23:15:48'),
(122, 40, 4, 0.00, 1, '2026-01-17 23:15:48'),
(123, 40, 5, 0.00, 1, '2026-01-17 23:15:48'),
(124, 40, 6, 0.00, 1, '2026-01-17 23:15:48');

-- --------------------------------------------------------

--
-- Estrutura para tabela `queues`
--

CREATE TABLE `queues` (
  `id` int(11) NOT NULL,
  `contact_id` int(11) DEFAULT NULL,
  `sector` varchar(50) DEFAULT NULL,
  `status` enum('waiting','attending','finished','transferred') DEFAULT 'waiting',
  `priority` int(11) DEFAULT 0,
  `user_id` int(11) DEFAULT NULL,
  `assigned_user_id` int(11) DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `finished_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `transferred_at` datetime DEFAULT NULL,
  `transferred_by` int(11) DEFAULT NULL,
  `transfer_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `quick_replies`
--

CREATE TABLE `quick_replies` (
  `id` int(11) NOT NULL,
  `sector` varchar(50) DEFAULT NULL,
  `title` varchar(100) NOT NULL,
  `content` text NOT NULL,
  `shortcut` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `quick_replies`
--

INSERT INTO `quick_replies` (`id`, `sector`, `title`, `content`, `shortcut`, `created_at`) VALUES
(2, 'Geral', 'Ola', 'Olá {{nome}}, tudo bem!', 'ola', '2025-07-14 12:07:46');

-- --------------------------------------------------------

--
-- Estrutura para tabela `requests`
--

CREATE TABLE `requests` (
  `id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `promo_type` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `target_audience` varchar(255) DEFAULT NULL,
  `duration` int(11) NOT NULL DEFAULT 7,
  `response_data` mediumtext NOT NULL,
  `from_cache` tinyint(1) NOT NULL DEFAULT 0,
  `feedback_provided` tinyint(1) NOT NULL DEFAULT 0,
  `positive_feedback` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `sessions`
--

CREATE TABLE `sessions` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `number` varchar(20) DEFAULT NULL,
  `qrcode` text DEFAULT NULL,
  `status` enum('disconnected','connecting','connected') DEFAULT 'disconnected',
  `connected_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `sessions`
--

INSERT INTO `sessions` (`id`, `name`, `number`, `qrcode`, `status`, `connected_at`, `created_at`) VALUES
(28, 'Valdecir0010', NULL, NULL, 'disconnected', NULL, '2025-10-20 14:37:02');

-- --------------------------------------------------------

--
-- Estrutura para tabela `social_notifications`
--

CREATE TABLE `social_notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `platform` enum('instagram','facebook') NOT NULL,
  `page_id` varchar(255) NOT NULL,
  `page_name` varchar(255) DEFAULT NULL,
  `access_token` text NOT NULL,
  `webhook_verify_token` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `social_notification_logs`
--

CREATE TABLE `social_notification_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `platform` varchar(50) NOT NULL,
  `sender_id` varchar(255) NOT NULL,
  `sender_name` varchar(255) DEFAULT NULL,
  `message_preview` text DEFAULT NULL,
  `message_full` text DEFAULT NULL,
  `timestamp` datetime NOT NULL,
  `read_status` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','json','boolean','number') DEFAULT 'string',
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `created_at`, `updated_at`) VALUES
(1, 'business_hours_enabled', 'true', 'boolean', 'Habilitar auto-resposta fora do horário', '2025-07-06 20:29:14', '2025-07-09 11:01:21'),
(2, 'business_hours_schedule', '{\"monday\":{\"enabled\":true,\"start\":\"08:00\",\"end\":\"18:00\"},\"tuesday\":{\"enabled\":true,\"start\":\"08:00\",\"end\":\"18:00\"},\"wednesday\":{\"enabled\":true,\"start\":\"08:00\",\"end\":\"18:00\"},\"thursday\":{\"enabled\":true,\"start\":\"08:00\",\"end\":\"18:00\"},\"friday\":{\"enabled\":true,\"start\":\"08:00\",\"end\":\"18:00\"},\"saturday\":{\"enabled\":false,\"start\":\"08:00\",\"end\":\"12:00\"},\"sunday\":{\"enabled\":false,\"start\":\"08:00\",\"end\":\"18:00\"}}', 'json', 'Horários de funcionamento por dia da semana', '2025-07-06 20:29:14', '2025-07-09 11:01:21'),
(3, 'business_hours_message', 'Olá! Nossa Agência está fechada no momento.\n\n📅 Horário de funcionamento:\nSegunda a Sexta: 8h às 18h\nSábado: Fechado\nDomingo: Fechado\n\nSua mensagem foi registrada e responderemos assim que possível!\n{{proximo_funcionamento}}', 'string', 'Mensagem enviada fora do horário comercial', '2025-07-06 20:29:14', '2025-07-09 11:01:21'),
(4, 'business_hours_holidays', '[]', 'json', 'Lista de feriados', '2025-07-06 20:29:14', '2025-07-09 11:01:21'),
(5, 'business_hours_exceptions', '[]', 'json', 'Exceções de horário', '2025-07-06 20:29:14', '2025-07-09 11:01:21'),
(16, 'auto_welcome_enabled', 'true', 'boolean', 'Habilitar mensagem automática de boas-vindas', '2025-07-08 16:05:37', '2025-07-10 14:36:25'),
(17, 'auto_welcome_message', '👋 Olá! Bem-vindo ao FarmaPro, um dos maiores ecossistema de marketing para farmácias do Brasil! Em breve um de nossos atendentes irá lhe atender.\nConfira nosso mais novo sistema de vendas integrado!\nhttps://farmapro.app.br/entrar/cardapio-qr/cardapio.php?token=daab33913ff2670bc68a224bf73dae8b', 'string', 'Mensagem de boas-vindas', '2025-07-08 16:05:37', '2025-07-10 14:36:25'),
(18, 'auto_goodbye_enabled', 'true', 'boolean', 'Habilitar mensagem automática de despedida', '2025-07-08 16:05:37', '2025-07-10 14:36:25'),
(19, 'auto_goodbye_message', '👋 Agradecemos seu contato! Caso precise de algo mais, estamos à disposição.', 'string', 'Mensagem de despedida', '2025-07-08 16:05:37', '2025-07-10 14:36:25'),
(20, 'auto_goodbye_signature', 'true', 'boolean', 'Incluir assinatura do atendente', '2025-07-08 16:05:37', '2025-07-10 14:36:25'),
(21, 'auto_goodbye_rating', 'false', 'boolean', 'Incluir pedido de avaliação', '2025-07-08 16:05:37', '2025-07-10 14:36:25'),
(22, 'polls_auto_save', 'true', 'boolean', 'Salvar enquetes automaticamente', '2025-07-08 16:05:37', '2025-07-10 14:36:25'),
(23, 'polls_auto_expire', 'true', 'boolean', 'Expirar enquetes automaticamente', '2025-07-08 16:05:37', '2025-07-10 14:36:25'),
(24, 'polls_expire_time', '24', 'number', 'Tempo para expirar (horas)', '2025-07-08 16:05:37', '2025-07-10 14:36:25'),
(25, 'polls_expire_action', 'close', 'string', 'Ação ao expirar', '2025-07-08 16:05:37', '2025-07-10 14:36:25'),
(26, 'polls_notify_response', 'true', 'boolean', 'Notificar respostas', '2025-07-08 16:05:37', '2025-07-10 14:36:25'),
(27, 'polls_notify_completion', 'true', 'boolean', 'Notificar conclusão', '2025-07-08 16:05:37', '2025-07-10 14:36:25'),
(28, 'polls_auto_confirm', 'true', 'boolean', 'Confirmar respostas automaticamente', '2025-07-08 16:05:38', '2025-07-10 14:36:25'),
(29, 'auto_message_delay', '2', 'number', 'Delay entre mensagens (segundos)', '2025-07-08 16:05:38', '2025-07-10 14:36:25'),
(30, 'auto_prevent_spam', 'true', 'boolean', 'Prevenir spam', '2025-07-08 16:05:38', '2025-07-10 14:36:25'),
(31, 'auto_spam_interval', '5', 'number', 'Intervalo anti-spam (minutos)', '2025-07-08 16:05:38', '2025-07-10 14:36:25'),
(32, 'auto_log_messages', 'true', 'boolean', 'Registrar mensagens automáticas', '2025-07-08 16:05:38', '2025-07-10 14:36:25'),
(33, 'auto_show_signature', 'false', 'boolean', 'Mostrar assinatura automática', '2025-07-08 16:05:38', '2025-07-10 14:36:25');

-- --------------------------------------------------------

--
-- Estrutura para tabela `tamanhos`
--

CREATE TABLE `tamanhos` (
  `id` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `descricao` varchar(100) DEFAULT NULL,
  `ordem_exibicao` int(11) DEFAULT 0,
  `ativo` tinyint(1) DEFAULT 1,
  `data_criacao` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Despejando dados para a tabela `tamanhos`
--

INSERT INTO `tamanhos` (`id`, `nome`, `descricao`, `ordem_exibicao`, `ativo`, `data_criacao`) VALUES
(1, 'RN', 'Recém-nascido (até 4kg)', 1, 1, '2025-07-11 19:24:53'),
(2, 'P', 'Pequeno (3-6kg)', 2, 1, '2025-07-11 19:24:53'),
(3, 'M', 'Médio (5-9kg)', 3, 1, '2025-07-11 19:24:53'),
(4, 'G', 'Grande (8-12kg)', 4, 1, '2025-07-11 19:24:53'),
(5, 'XG', 'Extra Grande (11-15kg)', 5, 1, '2025-07-11 19:24:53'),
(6, 'XXG', 'Extra Extra Grande (acima de 14kg)', 6, 1, '2025-07-11 19:24:53'),
(7, 'Único', 'Tamanho único', 7, 1, '2025-07-11 19:24:53'),
(8, 'PP', 'Extra Pequeno', 8, 1, '2025-07-11 19:24:53'),
(9, 'GG', 'Extra Grande', 9, 1, '2025-07-11 19:24:53');

-- --------------------------------------------------------

--
-- Estrutura para tabela `teste`
--

CREATE TABLE `teste` (
  `id` int(11) NOT NULL,
  `nome` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','supervisor','atendente') DEFAULT 'atendente',
  `sector` varchar(50) DEFAULT NULL,
  `signature` text DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `sector`, `signature`, `avatar`, `is_active`, `created_at`) VALUES
(1, 'Administrador', 'admin@admin.com', '$2a$10$uH45jYuFEaeEb5VnbpR4weibFkUYF6ryiQwArP.y6Drf.2bkeX4qy', 'admin', 'Geral', NULL, NULL, 1, '2025-07-06 20:29:14'),
(2, 'Valdecir', 'valdecir@vediese.com.br', '$2a$10$emGGoxVw/8oFOZWB1UzeUOnhrSrxNH1XCFfQSxcAK354Z38DmWZyW', 'atendente', 'Geral', 'Valdecir', NULL, 1, '2025-07-08 15:56:17'),
(3, 'Teste', '3000@vediese.com', '$2a$10$OXrv20jA.yw12184L7oDx.lRAgt60j.tEcw0LDbRuTdzHlgkmJxNy', 'atendente', 'Medicamento', 'Marciele', NULL, 1, '2025-08-12 13:38:18');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `senha` varchar(255) NOT NULL,
  `senha_real` varchar(255) DEFAULT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `is_client` tinyint(1) NOT NULL DEFAULT 0,
  `has_panel_access` tinyint(1) DEFAULT 0,
  `empresa` varchar(50) DEFAULT 'Viver Mais',
  `last_login` datetime DEFAULT NULL,
  `aceite_politicas_versao` varchar(50) DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `remember_token` varchar(64) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `avatar_updated_at` datetime DEFAULT NULL,
  `feedback_modal_exibido` tinyint(1) DEFAULT 0,
  `feedback_modal_data` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `usuario`, `email`, `senha`, `senha_real`, `is_admin`, `is_active`, `is_client`, `has_panel_access`, `empresa`, `last_login`, `aceite_politicas_versao`, `data_criacao`, `remember_token`, `avatar`, `avatar_updated_at`, `feedback_modal_exibido`, `feedback_modal_data`) VALUES
(1, 'admin', 'valdecir@vediese.com.br', '$2y$10$TIOzteOUjAn92IRpVjolUuwizvMvmTnhe.SqV5bs/tnIXxf6sJ4Y2', 'Valdecir2509*#', 1, 1, 0, 1, 'Viver Mais', '2026-01-20 13:48:31', '2025-07-23', '2025-02-15 02:34:55', NULL, NULL, NULL, 1, '2025-12-14 18:39:36'),
(10, 'Genilso', 'genilso.vivermais@hotmail.com', '$2y$10$bkVfeSzllNt/LZA5yOcq4uWX8tkQl6cDS.RTiJp/iLf3aJVH06PXe', 'vivermais', 0, 1, 1, 0, 'Viver Mais', '2026-01-15 17:52:29', '2025-07-23', '2025-02-17 12:35:48', NULL, NULL, NULL, 1, '2025-12-17 14:14:48'),
(11, 'Farmmattozo', NULL, '$2y$10$B/3G5qkU6EqOF78U4UXJde3qppt5WIOUAvYRLpZDoqLbqGUV7lOTa', 'farma628', 0, 0, 1, 0, 'Viver Mais', NULL, NULL, '2025-03-11 18:54:07', NULL, NULL, NULL, 0, NULL),
(12, 'xandilm', NULL, '$2y$10$0wHq1RNTBeVO0KZDVsirfeRIjtS9valBlftTQ7bnTq7B8JkS/oehq', 'aA9730376@', 0, 0, 1, 0, 'Viver Mais', NULL, '2025-07-23', '2025-03-14 18:00:55', NULL, NULL, NULL, 0, NULL),
(13, '00081998000104', NULL, '$2y$10$fPqW/ufgV/o7xPjr74eqOeBYdl8.5ZXUMtjweLwhAMm/en8eLlVKe', 'ctb161616', 0, 0, 1, 0, 'Viver Mais', NULL, '2025-07-23', '2025-03-14 18:40:48', NULL, NULL, NULL, 0, NULL),
(14, '04802018975', NULL, '$2y$10$Rk2AAp6kz//NIYs4c4Dk3e5.cMuGaH9vh/XvfR3o/YeBYvbXHtTeG', '30481222', 0, 0, 1, 0, 'Viver Mais', '2025-09-19 14:41:41', NULL, '2025-03-14 18:42:44', NULL, NULL, NULL, 0, NULL),
(15, '27091153000148', 'farmaciamedprev@gmail.com', '$2y$10$VAutwWIqjD082ltH1zSMhe0u9aX9bYdTdemEMBbtBLV4kQSXd0NMC', 'Senha123*', 0, 1, 1, 0, 'Viver Mais', '2026-01-15 13:21:09', '2025-07-23', '2025-03-14 18:46:22', NULL, NULL, NULL, 1, '2025-12-20 19:21:52'),
(16, 'farmaciasaude', 'farmacia.anderson@hotmail.com', '$2y$10$JGJPFb0QJRqhXydUi3zDdu4dYYTTxenNrLn8yFPakv6MKQ7Yvrhrq', '125740', 0, 0, 1, 0, 'Viver Mais', '2025-11-13 19:09:49', '2025-07-23', '2025-03-14 20:00:30', 'ab9443505922d119f832060f4ff1ab1202bd9d959adce55b5b3a14fad740d2d7', NULL, NULL, 0, NULL),
(17, '23197855000150', 'Farmacia_avenida17@outlook.com', '$2y$10$VGpMjql5gScC1uLwLcpsYOVUM3C7S2SNjBMB9zk4XuakWI9U4Jek6', 'Nm2$k2', 0, 1, 1, 0, 'Viver Mais', '2025-12-18 20:09:05', '2025-07-23', '2025-03-14 20:02:40', '350255457e93b87f6e0926c1fb1eb1308990ea8fac7c38126275d4db5748b48f', NULL, NULL, 1, '2025-12-15 13:43:29'),
(18, 'leandro', 'lemenezesfcia@gmail.com', '$2y$10$ouxZ3cpymBBzXsXKhOydBe2qfnpGeMH/u3QGjYZ1G8v8CHWnHrhuC', 'Farmacia22@', 0, 0, 1, 0, 'Viver Mais', '2025-09-15 16:08:51', '2025-07-23', '2025-03-14 20:21:04', NULL, NULL, NULL, 0, NULL),
(21, '08597417000195', NULL, '$2y$10$xeZgcvyPTtyideeVbbhq..A/FtYAFkxbTN9vEOnDQnqfMB83uyAei', '500108', 0, 0, 1, 0, 'Viver Mais', NULL, NULL, '2025-03-14 20:25:20', NULL, NULL, NULL, 0, NULL),
(22, 'Wesley', NULL, '$2y$10$PuAqPqPQZtYo9gQXNmc2ceexXFWXHA0cO6YDEesJiZcP0eCxYH1hi', 'casa1234', 0, 0, 1, 0, 'Viver Mais', NULL, NULL, '2025-03-14 23:40:51', NULL, NULL, NULL, 0, NULL),
(23, '09556787000147', 'ecofarmapopular@gmail.com', '$2y$10$RVlqPAzKfTStM7lmzbUPCuMcarGssebJEMcCVoeooyS32djBHQVge', 'WAM33361785@', 0, 1, 1, 0, 'Viver Mais', '2026-01-03 19:32:27', '2025-07-23', '2025-03-15 00:38:32', NULL, NULL, NULL, 1, '2025-12-18 19:28:41'),
(26, '14200480000149', NULL, '$2y$10$6445lB4C99NpaawkcQ1TweMcZ4l.9b.2SjY66mNFNUF7fHU.AU5qq', 'ap060482', 0, 0, 1, 0, 'Viver Mais', NULL, NULL, '2025-03-17 17:57:10', NULL, NULL, NULL, 0, NULL),
(27, 'SERGIO FUNAYAMA', NULL, '$2y$10$KBjuIHahtAGPWNM3VYPXU.kHME3ht42QA9esRY.FgPX5WUQ6mm2zG', '060154', 0, 0, 1, 0, 'Viver Mais', NULL, NULL, '2025-03-17 21:02:59', '14f8159cd7e6ad3b077b4625b50c0ac06f3e73e3e069c711e8e1c32e2b5f5563', NULL, NULL, 0, NULL),
(29, 'farmaciaprocofarma', 'farmaciaprocofarma@yahoo.com.br', '$2y$10$4GOOqGVkH11wBw10.ZIKduw.NkZR18gKS/.2Xx./Rvc5RKic1cyIG', '35242436', 0, 1, 1, 0, 'Viver Mais', '2025-12-22 10:43:20', '2025-07-23', '2025-03-18 14:08:51', '018dfa30e7332fddc7e12eb6942845e187ad2c42a548ef7ce08825f7384e863b', NULL, NULL, 1, '2025-12-15 10:59:20'),
(30, '05609462000124', NULL, '$2y$10$DPZDPLYlc8JpOXV.ZX6X9ebrzqwOoVpBxaj3.8KgKYTd6GFNWnDVa', '@casual123', 0, 0, 1, 0, 'Viver Mais', NULL, NULL, '2025-03-18 14:41:50', NULL, NULL, NULL, 0, NULL),
(31, '13878964000189', NULL, '$2y$10$J/OBeBuDa1x5ktBwF88N/uu9b.RdDEgt33g4timJJz3LDe27QDmu2', 'topfarma0910', 0, 0, 1, 0, 'Viver Mais', NULL, NULL, '2025-03-18 19:19:44', NULL, NULL, NULL, 0, NULL),
(32, 'miguelvfilho', 'miguelvfilho@hotmail.com', '$2y$10$dU.iaw.Mt0zrUl/IOhnpWu4MUriZ0g9POyrYgUBXQrQWnehQ68xO6', 'Senha1234*', 0, 1, 1, 0, 'Viver Mais', '2026-01-19 20:30:44', '2025-07-23', '2025-03-18 23:58:10', NULL, NULL, NULL, 1, '2025-12-17 14:36:42'),
(33, '37164776000157', 'farmaciavivermaisipora@gmail.com', '$2y$10$YngqXtGhbTumHE8K88lpne9Ik78O30r2cj3azXu32IQoBPZ87Q4Nm', 'Viver1315', 0, 1, 1, 0, 'Viver Mais', '2025-12-13 22:10:28', '2025-07-23', '2025-03-19 17:21:04', NULL, NULL, NULL, 0, NULL),
(35, '25063', NULL, '$2y$10$qTC03nUs33PuzqbKifOVpudWobsbZU6ov5sEhN8RRZ7qG4hTnPoze', '01152', 0, 0, 1, 0, 'Viver Mais', NULL, NULL, '2025-03-24 12:55:37', NULL, NULL, NULL, 0, NULL),
(37, 'FARMACIA3000-F1', NULL, '$2y$10$4NGU5fk1wXSpva72QudMnuhGAuEjek75ynAqDG/rBa30OYmdCAK2S', 'FARMACIA3001', 0, 0, 1, 0, 'Rede 3000', '2025-09-05 14:09:32', NULL, '2025-03-26 19:21:33', NULL, NULL, NULL, 0, NULL),
(40, 'recrutador', 'xxgalaxixx3@gmail.com', '$2y$10$IDmUmn.xEZyYBrEaD2vvfev6t0icJMpJZvOR45BFcoxgBO33hCacq', 'cv2024demo', 0, 1, 1, 0, 'Viver Mais', '2025-12-19 14:22:11', '2025-07-23', '2025-04-01 00:53:06', NULL, NULL, NULL, 1, '2025-12-14 18:54:40'),
(41, '80351364000155', NULL, '$2y$10$fl28N9Zzs9p9Awcg5DJ.G.FvBXgYLgMmtFhuuc85uQPDzgeJ.Zj5y', 'Desfrin1953@', 0, 0, 1, 0, 'Viver Mais', NULL, '2025-07-23', '2025-04-01 12:37:25', 'd6ad66e198f94e43dab6ca6052e077eea51da552edbc6a85d3da8ebfef44463c', NULL, NULL, 0, NULL),
(47, 'deusdete', NULL, '$2y$10$55f2wxfFlrwAOwVJBg0ruu5SXyl1jpzO3kcBw1NajkEnJd3fgOQCS', '23052016', 0, 0, 1, 0, 'Viver Mais', '2025-09-19 14:40:54', '2025-07-23', '2025-04-07 17:18:15', NULL, NULL, NULL, 0, NULL),
(48, 'farmacia3000pb@gmail.com', NULL, '$2y$10$qRjmRZNR8hVAFc.y4jdxXuuT8rfM1sMA6mfsb2NNLu0wW1Uxbr8fu', 'farma2112', 0, 0, 1, 0, 'Rede 3000', NULL, NULL, '2025-04-08 19:11:47', '8eead26f14131393a2f7a8c850ebc5347e00b8295037ac0251ea2332e66062c2', NULL, NULL, 0, NULL),
(50, 'catanduvas', 'catanduvas@uol.com.br', '$2y$10$jJy.h9zGdN9aBZC2b6FA9u8IUAA3QkTNhjr6s.3cSJ6yo70g4KjOK', 'Edezio61@', 0, 0, 1, 0, 'Viver Mais', NULL, '2025-07-23', '2025-04-09 19:31:21', NULL, NULL, NULL, 0, NULL),
(51, 'farmacia3000clevelandia@gmail.com', NULL, '$2y$10$0cBu0e1XFBkIY8qD8xWWEua1/wUDRRgYJE33KW//EY9fdznspBtTi', 'farma0608', 0, 0, 1, 0, 'Rede 3000', NULL, NULL, '2025-04-10 12:52:09', 'a242519f79e90a6c141767ccf79e8fc10c970b6d6c27d3ff128560f3e4bde848', NULL, NULL, 0, NULL),
(54, 'jezyt', 'jezyt@hotmail.com', '$2y$10$FkGWvy7C57FYlBrruwsII.vZLqAAJuDEktduwBSszPOG6vccMDEUq', 'farma0608', 0, 0, 1, 0, 'Viver Mais', NULL, '2025-07-23', '2025-04-10 18:09:52', NULL, NULL, NULL, 0, NULL),
(56, 'Loiane', NULL, '$2y$10$WLHJXFMTkgjQm2eDGk1sWePnXHHyu5SWLHN/imNv8KFkUWlW2iWE.', '0315', 0, 1, 1, 0, 'Rede 3000', '2026-01-11 20:49:47', NULL, '2025-04-11 14:50:50', NULL, NULL, NULL, 0, NULL),
(60, '80003221000152', 'farmaciasaomanoel@gmail.com', '$2y$10$cBuOewIsiCF2AXzJp5m96OzauRYeDkHsecyFHUwKEVRYC7BK5c2XO', '03022018', 0, 0, 1, 0, 'Viver Mais', '2025-10-15 09:56:08', '2025-07-23', '2025-04-15 14:43:24', NULL, NULL, NULL, 0, NULL),
(63, '445566', NULL, '$2y$10$lXhouACUUX9kKP5T0WtgY.wWOPhIH4fqf.wnVcrYBVHxgGCh5n3bS', '#Novo25#', 0, 0, 1, 0, 'Viver Mais', NULL, NULL, '2025-04-16 13:19:52', NULL, NULL, NULL, 0, NULL),
(64, 'kelin', NULL, '$2y$10$oUDm2/64m/FGn4ANcvSiP.ae4hOQZJQMhqhZGvx70nxBFRAzoArqS', '241211', 0, 0, 1, 0, 'FarmaPro', NULL, NULL, '2025-04-30 13:13:19', NULL, NULL, NULL, 0, NULL),
(66, '75138750000123', NULL, '$2y$10$FRvSIzoJz30nL8T.4Xb/YOnJ8mPSPTi7XJh6flFoueSG9a3KCmLtm', 'brasil16', 0, 0, 1, 0, 'Viver Mais', NULL, NULL, '2025-05-29 14:02:38', NULL, NULL, NULL, 0, NULL),
(69, '02887193000170', NULL, '$2y$10$6eflnMJX47hn9txqt1UXZOD7qPOGkdh5pcLrMgpBNWEfazzMKA7/C', 'ctb161616', 0, 0, 1, 0, 'Viver Mais', NULL, NULL, '2025-07-09 14:22:54', NULL, NULL, NULL, 0, NULL),
(70, 'LIDERFARMA', NULL, '$2y$10$AQw3GzC4llTbqlqvEU0zXOEXt1vKCgM3J9fUoK2A/K8O8Y7IYWRmC', '1688', 0, 0, 1, 0, 'Viver Mais', NULL, '2025-07-23', '2025-07-23 14:26:01', NULL, NULL, NULL, 0, NULL),
(71, 'evandro agostini', 'evandro_farma010@hotmail.com', '$2y$10$D7ElQ.ibpN9sxQWxDhTDEOsFVTPCXBSYI4ZgR.yZxvKqcESY.lRlK', '32431934', 0, 0, 1, 0, 'Viver Mais', NULL, NULL, '2025-08-01 12:37:48', NULL, NULL, NULL, 0, NULL),
(72, 'Douglas', 'farmacia_dopaulo@hotmail.com', '$2y$10$LN94Elr/qZ9mZrl57eDWHOaOY7bE1n4FUc7SZyBoXmioDm0RS8bTy', 'aerolin1263', 0, 0, 1, 0, 'Viver Mais', '2025-08-20 11:38:45', '2025-07-23', '2025-08-01 15:16:39', NULL, NULL, NULL, 0, NULL),
(73, 'Evandro', 'fcia.farmacenter@gmail.com', '$2y$10$KdCqIuoDaMQ8Bf8u.CFwwuwJt80su8G9iOzxDGbWvvzdUBYG0gam2', '35682270', 0, 0, 1, 0, 'Viver Mais', '2025-10-04 15:00:09', '2025-07-23', '2025-08-01 18:22:37', NULL, NULL, NULL, 0, NULL),
(75, 'Farmácia Farmareis', 'emerson.lazarini@yahoo.com.br', '$2y$10$iobWJ4KHBurvap88HJqzvOq.kRwf9Y8SzLaNXJ8XvlXocK1SXSlSK', 'Cliente123', 0, 1, 1, 0, 'Viver Mais', '2026-01-11 20:50:02', '2025-07-23', '2025-08-20 13:21:35', NULL, NULL, NULL, 1, '2026-01-11 20:50:06'),
(76, 'Farmacia Santo Antonio', 'farmaciasantoantonio@farmapro.app.br', '$2y$10$xfOAgEgLo8pDeXwwbivw8u6Zx7TgtQH.NDxldX2yb81qeteMJNFaW', 'manager753', 0, 1, 1, 0, 'Viver Mais', '2026-01-11 20:49:17', '2025-07-23', '2025-09-10 02:16:53', NULL, NULL, NULL, 1, '2026-01-11 20:50:21'),
(78, 'miriam monteiro', 'farmaciatamoio@outlook.com', '$2y$10$nJXlxH7CKL8R.GCfcQr43eWmaCAq2aBFRulI0KIBZV7sfk5SBALIG', 'farmaciatamoio36221947', 0, 0, 1, 0, 'FarmaPro', NULL, NULL, '2025-09-10 13:00:19', NULL, NULL, NULL, 0, NULL),
(79, 'cris_p_toledo@hotmail.com', 'cris_p_toledo@hotmail.com', '$2y$10$P7RYEQq7xsEihRcIstYqEuFhYkXZJBsVYFmTMnvzUpzOUmyP/WPRe', '016574', 0, 0, 1, 0, 'Viver Mais', NULL, NULL, '2025-09-28 18:24:04', NULL, NULL, NULL, 0, NULL),
(80, '11662154000165', 'farmaparecidabraga@gmail.com', '$2y$10$HOIoPGSxkQEG0j23z31ElOcPgvsNtqje2h5X27/7KL55YfNXB4VCa', '131285', 0, 0, 1, 0, 'Viver Mais', '2025-11-21 17:42:12', '2025-07-23', '2025-09-29 20:02:37', 'd7fb74f21005c065600a1b690d0de4f3ee092cd3c476974564e798307fdcaa8c', NULL, NULL, 0, NULL),
(82, 'marxfarma', 'pauloconvenios@bol.com.br', '$2y$10$3FYE/6UkkJIG9mScqObdp.CuakEjHFVxV52/a4Umo98SZoraVF8GO', '34241213', 0, 0, 1, 0, 'Viver Mais', '2025-11-04 08:37:29', '2025-07-23', '2025-10-30 16:08:14', '98024fa503f947d12fef61d92c268637b0698a4a5d8689e17bb0216041a9b193', NULL, NULL, 0, NULL),
(83, 'marcos', 'f.dermatoflora@hotmail.com', '$2y$10$apwN5djX6pRSa35vSk7hq.GYZIZR3bFlabo.qItaYL2lMSsGV5Fre', 'marx0802', 0, 0, 1, 0, 'Viver Mais', '2025-11-26 15:09:07', '2025-07-23', '2025-10-30 17:08:21', NULL, NULL, NULL, 0, NULL),
(85, '22202179000283', 'ferraz-jorge@hotmail.com', '$2y$10$itYU3.3oFtOFUXdwQKKnquCRvjQsYdW2K5RMF/pNpTpN/DUbEFhZ2', '906510Rc#$', 0, 0, 1, 0, 'Viver Mais', NULL, NULL, '2025-11-29 23:01:30', NULL, NULL, NULL, 0, NULL),
(88, 'Farmacia Viver Mais Urai', 'vivermaisurai309@gmail.com', '$2y$10$p.qBD4/6hrpZYRQODIx/Ue4JwhRcSuKkQ0tOaRSGpdVcbOnYsVAJS', 'Pedro24112012', 0, 0, 1, 0, 'Viver Mais', NULL, NULL, '2025-12-17 21:04:51', NULL, NULL, NULL, 0, NULL),
(91, 'Manoel', 'manodeazevedo@gmail.com', '$2y$10$ojhV5ci2JcvF66JE9lW8FeFP1xZyFesW0IMum86H.OxTVqjAWFUxC', '19735800', 0, 1, 1, 0, 'Viver Mais', '2026-01-19 11:48:39', '2025-07-23', '2026-01-09 14:31:51', 'aea3e279e5023df96cbe32ae00518e8e2519be453e5a0f7f7ca3b49f9a852550', NULL, NULL, 0, NULL),
(92, 'nadirde lima', 'negrao.lima@uol.com.br', '$2y$10$DTe4DeFDlfbQ9ObEpgeuIeZNL4RQwCvpkKz1ALsDVaNlqafd65Yeu', 'negrao.lima@uol.com.br', 0, 1, 1, 0, 'Viver Mais', '2026-01-12 13:58:29', '2025-07-23', '2026-01-12 13:40:57', NULL, NULL, NULL, 0, NULL),
(93, 'megafarmaem@gmail.com', 'megafarmaem@gmail.com', '$2y$10$QffiIuD3TlfQGmin9ukO..f2R8vtlAUmqbMX96ZXSZg6jvSpygGu2', 'MEGAFARMAEM@GMIAL.COM', 0, 1, 1, 0, 'Viver Mais', NULL, NULL, '2026-01-12 17:51:20', NULL, NULL, NULL, 0, NULL),
(94, 'RICARDO', 'farmavidawbraz@hotmail.com', '$2y$10$cVmtaxl245clEQZ6s6RQzubjoohyqx1irQo4OJWLQYIKAGfs9E/di', '35283210', 0, 1, 1, 0, 'Viver Mais', '2026-01-13 11:20:49', '2025-07-23', '2026-01-12 19:08:26', NULL, NULL, NULL, 0, NULL),
(96, 'RICARDO RODRIGUES', 'dtopfarma2@hotmail.com', '$2y$10$4XG3kaJE0wX.i2b1Rhv1FuWbP1g5tUhYLcvF3cHwqzaW/k6qTn/Vy', 'TOPfarma250407@', 0, 1, 1, 0, 'Viver Mais', NULL, NULL, '2026-01-13 15:12:40', NULL, NULL, NULL, 0, NULL),
(97, 'fsantonio', 'fsantonio.adm@gmail.com', '$2y$10$jfdqY4WfWU1Hc0HYe8QinOWGj3u./8RZIN.FRGBQCVWQP7TY7NeYe', '11141282', 0, 1, 1, 0, 'Viver Mais', '2026-01-14 00:12:01', '2025-07-23', '2026-01-13 19:18:31', NULL, NULL, NULL, 0, NULL),
(98, 'franciele', 'fbjronda@hotmail.com', '$2y$10$U5rIhQ3pOSowQ951efQ6C.R8neznfJOwl/hSTm/AJMydTJkwe9FQS', 'farmacia1985', 0, 1, 1, 0, 'Viver Mais', '2026-01-16 16:58:11', '2025-07-23', '2026-01-15 12:39:14', NULL, NULL, NULL, 0, NULL),
(99, 'Uli', 'ulifarma100@hotmail.com', '$2y$10$Jpq2EXbXf/mJA8njaWzhruI1WWAOJnyAK2NDnqOXo5mWDnsea4v9q', 'Senha1234*', 0, 1, 1, 0, 'Viver Mais', NULL, NULL, '2026-01-16 23:30:30', NULL, NULL, NULL, 0, NULL),
(100, 'egomar', 'egboni@hotmail.com', '$2y$10$De3EXZTw4iArtshFYG28vu.REs2NVKdTUaTOHhNvBP2CSFZOGpls2', '123456', 0, 1, 1, 0, 'Viver Mais', '2026-01-19 16:06:37', '2025-07-23', '2026-01-19 14:34:04', 'fd15bcef2a79afbd54a4bc052fd9771f69849a958311f5d19cb796986db2a9ac', NULL, NULL, 0, NULL),
(101, 'Bianca', 'biancaamorimdasilva29@gmail.com', '$2y$10$MrVMFjzitXvTB9hzVEcH..API7JtWmtkhRe8cGz4hgKbq0dzRBJTK', '11062020', 0, 1, 1, 0, 'Viver Mais', '2026-01-20 14:29:39', '2025-07-23', '2026-01-20 13:48:17', NULL, NULL, NULL, 0, NULL);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `campaigns`
--
ALTER TABLE `campaigns`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `campaign_logs`
--
ALTER TABLE `campaign_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_campaign` (`campaign_id`),
  ADD KEY `idx_contact` (`contact_id`),
  ADD KEY `idx_status` (`status`);

--
-- Índices de tabela `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `chat_session_id` (`chat_session_id`);

--
-- Índices de tabela `chat_sessions`
--
ALTER TABLE `chat_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_id` (`session_id`);

--
-- Índices de tabela `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `number` (`number`);

--
-- Índices de tabela `contact_notes`
--
ALTER TABLE `contact_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contact` (`contact_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Índices de tabela `contact_tags`
--
ALTER TABLE `contact_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Índices de tabela `contact_tag_relations`
--
ALTER TABLE `contact_tag_relations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_contact_tag` (`contact_id`,`tag_id`),
  ADD KEY `idx_contact` (`contact_id`),
  ADD KEY `idx_tag` (`tag_id`);

--
-- Índices de tabela `contatos`
--
ALTER TABLE `contatos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `email_campanhas`
--
ALTER TABLE `email_campanhas`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `farmacias`
--
ALTER TABLE `farmacias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `qr_code_token` (`qr_code_token`),
  ADD UNIQUE KEY `pixel_id` (`pixel_id`),
  ADD KEY `idx_farmacia_usuario` (`usuario_id`);

--
-- Índices de tabela `feedbacks`
--
ALTER TABLE `feedbacks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_data` (`data_criacao`),
  ADD KEY `idx_lido` (`lido`),
  ADD KEY `idx_status` (`status`);

--
-- Índices de tabela `feedback_details`
--
ALTER TABLE `feedback_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_request_feedback` (`request_id`,`feedback_type`);

--
-- Índices de tabela `keywords`
--
ALTER TABLE `keywords`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_request_keyword` (`request_id`,`keyword`),
  ADD KEY `idx_keyword` (`keyword`);

--
-- Índices de tabela `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contact` (`contact_id`),
  ADD KEY `idx_session` (`session_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_from_number` (`from_number`);

--
-- Índices de tabela `pedidos_whatsapp`
--
ALTER TABLE `pedidos_whatsapp`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_farmacia_pedido` (`farmacia_id`),
  ADD KEY `idx_status_pedido` (`status_pedido`),
  ADD KEY `idx_data_criacao` (`data_criacao`);

--
-- Índices de tabela `permissoes_pedidos`
--
ALTER TABLE `permissoes_pedidos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_usuario` (`usuario_id`);

--
-- Índices de tabela `pixel_eventos`
--
ALTER TABLE `pixel_eventos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `event_id` (`event_id`),
  ADD KEY `idx_pixel_evento` (`pixel_id`,`event_time`),
  ADD KEY `idx_farmacia_evento` (`farmacia_id`,`event_time`);

--
-- Índices de tabela `polls`
--
ALTER TABLE `polls`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contact` (`contact_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`);

--
-- Índices de tabela `poll_responses`
--
ALTER TABLE `poll_responses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_poll_response` (`poll_id`,`contact_id`),
  ADD KEY `idx_poll` (`poll_id`),
  ADD KEY `idx_contact` (`contact_id`);

--
-- Índices de tabela `produtos`
--
ALTER TABLE `produtos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_farmacia_produto` (`farmacia_id`),
  ADD KEY `idx_categoria_produto` (`categoria_id`),
  ADD KEY `idx_produto_tarja` (`tarja`);

--
-- Índices de tabela `produto_tamanhos`
--
ALTER TABLE `produto_tamanhos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_produto_tamanho` (`produto_id`,`tamanho_id`),
  ADD KEY `tamanho_id` (`tamanho_id`);

--
-- Índices de tabela `queues`
--
ALTER TABLE `queues`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status_sector` (`status`,`sector`),
  ADD KEY `idx_assigned_user` (`assigned_user_id`);

--
-- Índices de tabela `quick_replies`
--
ALTER TABLE `quick_replies`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product` (`product_name`),
  ADD KEY `idx_promo_type` (`promo_type`),
  ADD KEY `idx_feedback` (`feedback_provided`,`positive_feedback`);

--
-- Índices de tabela `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `social_notifications`
--
ALTER TABLE `social_notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_platform_page` (`user_id`,`platform`,`page_id`);

--
-- Índices de tabela `social_notification_logs`
--
ALTER TABLE `social_notification_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_platform` (`user_id`,`platform`),
  ADD KEY `idx_timestamp` (`timestamp`);

--
-- Índices de tabela `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Índices de tabela `tamanhos`
--
ALTER TABLE `tamanhos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `teste`
--
ALTER TABLE `teste`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD UNIQUE KEY `idx_email_unique` (`email`),
  ADD KEY `idx_usuario` (`usuario`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_admin` (`is_admin`),
  ADD KEY `idx_client` (`is_client`),
  ADD KEY `idx_remember_token` (`remember_token`),
  ADD KEY `idx_empresa` (`empresa`),
  ADD KEY `idx_aceite_politicas_versao` (`aceite_politicas_versao`),
  ADD KEY `idx_email_search` (`email`),
  ADD KEY `idx_feedback_modal` (`feedback_modal_exibido`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `campaigns`
--
ALTER TABLE `campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `campaign_logs`
--
ALTER TABLE `campaign_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=297;

--
-- AUTO_INCREMENT de tabela `chat_sessions`
--
ALTER TABLE `chat_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- AUTO_INCREMENT de tabela `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `contact_notes`
--
ALTER TABLE `contact_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `contact_tags`
--
ALTER TABLE `contact_tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `contact_tag_relations`
--
ALTER TABLE `contact_tag_relations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `contatos`
--
ALTER TABLE `contatos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `email_campanhas`
--
ALTER TABLE `email_campanhas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `farmacias`
--
ALTER TABLE `farmacias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT de tabela `feedbacks`
--
ALTER TABLE `feedbacks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `feedback_details`
--
ALTER TABLE `feedback_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `keywords`
--
ALTER TABLE `keywords`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pedidos_whatsapp`
--
ALTER TABLE `pedidos_whatsapp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `permissoes_pedidos`
--
ALTER TABLE `permissoes_pedidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pixel_eventos`
--
ALTER TABLE `pixel_eventos`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `polls`
--
ALTER TABLE `polls`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `poll_responses`
--
ALTER TABLE `poll_responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `produtos`
--
ALTER TABLE `produtos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT de tabela `produto_tamanhos`
--
ALTER TABLE `produto_tamanhos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=125;

--
-- AUTO_INCREMENT de tabela `queues`
--
ALTER TABLE `queues`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `quick_replies`
--
ALTER TABLE `quick_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `requests`
--
ALTER TABLE `requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `sessions`
--
ALTER TABLE `sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT de tabela `social_notifications`
--
ALTER TABLE `social_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `social_notification_logs`
--
ALTER TABLE `social_notification_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=98;

--
-- AUTO_INCREMENT de tabela `tamanhos`
--
ALTER TABLE `tamanhos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `teste`
--
ALTER TABLE `teste`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=102;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `api_logs`
--
ALTER TABLE `api_logs`
  ADD CONSTRAINT `api_logs_ibfk_1` FOREIGN KEY (`farmacia_id`) REFERENCES `farmacias` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`chat_session_id`) REFERENCES `chat_sessions` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `farmacias`
--
ALTER TABLE `farmacias`
  ADD CONSTRAINT `farmacias_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `feedbacks`
--
ALTER TABLE `feedbacks`
  ADD CONSTRAINT `feedbacks_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `feedback_details`
--
ALTER TABLE `feedback_details`
  ADD CONSTRAINT `feedback_details_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `keywords`
--
ALTER TABLE `keywords`
  ADD CONSTRAINT `keywords_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `pedidos_whatsapp`
--
ALTER TABLE `pedidos_whatsapp`
  ADD CONSTRAINT `pedidos_whatsapp_ibfk_1` FOREIGN KEY (`farmacia_id`) REFERENCES `farmacias` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `pixel_eventos`
--
ALTER TABLE `pixel_eventos`
  ADD CONSTRAINT `pixel_eventos_ibfk_1` FOREIGN KEY (`farmacia_id`) REFERENCES `farmacias` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `produtos`
--
ALTER TABLE `produtos`
  ADD CONSTRAINT `produtos_ibfk_1` FOREIGN KEY (`farmacia_id`) REFERENCES `farmacias` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `produtos_ibfk_2` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`);

--
-- Restrições para tabelas `produto_tamanhos`
--
ALTER TABLE `produto_tamanhos`
  ADD CONSTRAINT `produto_tamanhos_ibfk_1` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `produto_tamanhos_ibfk_2` FOREIGN KEY (`tamanho_id`) REFERENCES `tamanhos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `social_notifications`
--
ALTER TABLE `social_notifications`
  ADD CONSTRAINT `social_notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `social_notification_logs`
--
ALTER TABLE `social_notification_logs`
  ADD CONSTRAINT `social_notification_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
