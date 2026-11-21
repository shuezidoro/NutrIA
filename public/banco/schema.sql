CREATE DATABASE IF NOT EXISTS `nutria` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `nutria`;

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `data_cadastro` timestamp NULL DEFAULT current_timestamp(),
  `email_verificado` tinyint(1) DEFAULT 0,
  `token_verificacao` varchar(64) DEFAULT NULL,
  `codigo_expira_em` datetime DEFAULT NULL,
  `tipo_usuario` enum('usuario','admin') DEFAULT 'usuario',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ;

CREATE TABLE `biometria` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `peso` decimal(5,2) NOT NULL,
  `altura` decimal(3,2) NOT NULL,
  `idade` int(3) NOT NULL,
  `genero` enum('Masculino','Feminino') NOT NULL,
  `nivel_atividade` enum('Sedentario','Leve','Moderado','Ativo','Muito_Ativo') NOT NULL,
  `objetivo` enum('Perda_Peso','Manter_Saude','Ganho_Massa') NOT NULL,
  `ultima_atualizacao` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `biometria_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ;

CREATE TABLE `historico_peso` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `peso` decimal(5,2) NOT NULL,
  `data_registro` date NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario_data` (`usuario_id`,`data_registro`),
  CONSTRAINT `historico_peso_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
);

CREATE TABLE `metas_diarias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `kcal_meta` decimal(8,2) NOT NULL,
  `proteina_meta` decimal(8,2) NOT NULL,
  `carbo_meta` decimal(8,2) NOT NULL,
  `gordura_meta` decimal(8,2) NOT NULL,
  `origem_meta` enum('Sistema','Manual') DEFAULT 'Sistema',
  `data_atualizacao` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario_id_2` (`usuario_id`),
  CONSTRAINT `metas_diarias_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
);

CREATE TABLE `refeicao` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `descricao_texto` text NOT NULL,
  `tipo_refeicao` varchar(50) DEFAULT NULL,
  `data_consumo` date NOT NULL,
  `criado_em` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `refeicao_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ;

CREATE TABLE `nutriente` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `refeicao_id` int(11) NOT NULL,
  `calorias_obtidas` decimal(8,2) DEFAULT 0.00,
  `proteina_obtida` decimal(8,2) DEFAULT 0.00,
  `carbo_obtido` decimal(8,2) DEFAULT 0.00,
  `gordura_obtida` decimal(8,2) DEFAULT 0.00,
  `acucar_obtido` decimal(5,2) DEFAULT 0.00,
  `fibra_obtida` decimal(5,2) DEFAULT 0.00,
  `sodio_obtido` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `refeicao_id` (`refeicao_id`),
  CONSTRAINT `nutriente_ibfk_1` FOREIGN KEY (`refeicao_id`) REFERENCES `refeicao` (`id`) ON DELETE CASCADE
);

CREATE TABLE `refeicoes_favoritas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `nome_favorito` varchar(100) NOT NULL,
  `descricao_comida` text NOT NULL,
  `calorias` decimal(8,2) NOT NULL DEFAULT 0.00,
  `proteina` decimal(8,2) NOT NULL DEFAULT 0.00,
  `carbo` decimal(8,2) NOT NULL DEFAULT 0.00,
  `gordura` decimal(8,2) NOT NULL DEFAULT 0.00,
  `acucar` decimal(5,2) NOT NULL DEFAULT 0.00,
  `fibra` decimal(5,2) NOT NULL DEFAULT 0.00,
  `sodio` decimal(10,2) NOT NULL DEFAULT 0.00,
  `criado_em` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `refeicoes_favoritas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
);

CREATE TABLE `reset_senha` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expira_em` datetime NOT NULL,
  `usado` tinyint(1) DEFAULT 0,
  `criado_em` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `reset_senha_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
);

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `data_cadastro`, `email_verificado`, `token_verificacao`, `codigo_expira_em`, `tipo_usuario`) VALUES
(1, 'adm', 'suportenutria18@gmail.com', '$2y$10$pOuE2WkDdg4PJSp4e1HMcOc6/qvHkiCTNt5Egxy5o1usdg33u.ISC', '2026-08-19 20:46:06', 1, NULL, NULL, 'admin');


INSERT INTO `metas_diarias` (`id`, `usuario_id`, `kcal_meta`, `proteina_meta`, `carbo_meta`, `gordura_meta`, `origem_meta`, `data_atualizacao`) VALUES
(1, 1, 1645.00, 123.00, 165.00, 55.00, 'Sistema', '2026-08-19 20:46:38');