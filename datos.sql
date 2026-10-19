-- =========================================================
--  EncuentraME - Base de datos
--  Importar desde phpMyAdmin (opcional: el sistema también
--  crea la base y la tabla automáticamente).
-- =========================================================

CREATE DATABASE IF NOT EXISTS encuentrame
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

USE encuentrame;

CREATE TABLE IF NOT EXISTS personas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  dni VARCHAR(12) NOT NULL DEFAULT '',
  nombres VARCHAR(150) NOT NULL,
  alias VARCHAR(60) NOT NULL DEFAULT '',
  redes VARCHAR(255) NOT NULL DEFAULT '',
  estado VARCHAR(20) NOT NULL DEFAULT 'por revisar',
  notas VARCHAR(600) NOT NULL DEFAULT '',
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO personas (dni, nombres, alias, redes, estado, notas) VALUES
('45678901', 'Ríos Quispe María Elena', 'mariel.rios', 'Instagram, TikTok', 'por revisar', 'Cuenta principal de la feria de ciencias.'),
('41234567', 'Torres Gálvez Luis Fernando', 'luisfg_dev', 'GitHub, Dev.to', 'revisado', 'Expone el proyecto de redes en GitHub.'),
('70123456', 'Medina Castillo Ana Lucía', 'ana.medina', 'Instagram, YouTube', 'por revisar', 'Videoblog de la biblioteca.'),
('46789012', 'Salazar Ruiz Diego', 'diegosz', 'X, Reddit', 'descartado', 'Cuenta sin actividad reciente.'),
('43567890', 'Quispe Mamani Roxana', 'roxanaq', 'Facebook, TikTok', 'por revisar', 'Organiza eventos del colegio.'),
('72345678', 'Vargas León Cristian', 'cvargas', 'GitHub, Stack Overflow', 'revisado', 'Aporta al repositorio del curso.'),
('44890123', 'Flores Núñez Gabriela', 'gabyflores', 'Instagram', 'por revisar', 'Consulta sobre horarios de atención.'),
('41765432', 'Paredes Soto Renzo', 'renzo.p', 'Twitch, YouTube', 'por revisar', 'Transmisiones en vivo de videojuegos.'),
('48901234', 'Aguilar Fernández Karla', 'karla.ag', 'Dev.to, GitHub', 'revisado', 'Portafolio de diseño web.'),
('40234567', 'Chávez Mendoza Iván', 'ivanch', 'Reddit, Hacker News', 'por revisar', 'Comenta noticias de tecnología.'),
('71456789', 'Espinoza Ramos Teresa', 'tespi', 'Facebook', 'por revisar', 'Contacto de la asociación de padres.'),
('45987654', 'Zamudio Cruz Patricio', 'pzamudio', 'X, Instagram', 'descartado', 'Perfil duplicado.'),
('42345678', 'Ramos Ortiz Sergio', 'sramos', 'GitHub', 'revisado', 'Copia del proyecto de la clase.'),
('49876543', 'Herrera Soto Mónica', 'monicah', 'TikTok, Instagram', 'por revisar', 'Campaña de reciclaje.'),
('47654321', 'Núñez Prado Andrés', 'anprado', 'Stack Overflow, GitHub', 'revisado', 'Resuelve dudas de PHP.'),
('46543218', 'Loayza Ponce Camila', 'camiloayza', 'YouTube, Twitch', 'por revisar', 'Canal de divulgación científica.');
