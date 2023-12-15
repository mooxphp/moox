-- -------------------------------------------------------------
-- TablePlus 5.6.6(520)
--
-- https://tableplus.com/
--
-- Database: moox
-- Generation Time: 2023-12-14 09:46:33.6180
-- -------------------------------------------------------------


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `current_team_id`, `profile_photo_path`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'alf@drollinger.info', NULL, '$2y$10$xkh.Ak433tGhMPt7FRqhXOeoFdQD2DvLnjJAWEqZR32i293.tfKKC', 'qLkHQBqXiHQp1D0ahyHAKN1biSto8E5iVwo9fAfuOVISwQyXalx8xOtM6Iwg', NULL, NULL, '2023-11-30 18:22:40', '2023-11-30 18:22:40'),
(2, 'Alf', 'alf@alf-drollinger.com', NULL, '$2y$10$2tmqvVbAFqK5z6knCEKo5.0.nCkvDyiM/jzhtYiWEZz6YB23nrKoW', NULL, NULL, NULL, '2023-12-14 08:37:56', '2023-12-14 08:37:56'),
(3, 'Aziz', 'aziz.gasim@heco.de', NULL, '$2y$10$OdeNKC.5jTffdRAELig10OoL4pOr5jjMRU8s8RBNNzWVJsXqMiHKm', NULL, NULL, NULL, '2023-12-14 08:41:04', '2023-12-14 08:41:04'),
(4, 'Kim', 'kim.speer@co-it.eu', NULL, '$2y$10$2pGnLlx66g.McfNrTFSIuOuFtihJ5AD7uZBnSL0nrl1Um6oeEd69e', NULL, NULL, NULL, '2023-12-14 08:42:11', '2023-12-14 08:42:11'),
(5, 'Reinhold', 'reinhold.jesse@heco.de', NULL, '$2y$10$YPlslkcT31C1/zOxB.O88OFq6S1jBpHSj5qAAzGFoAaF9Maie5rUG', NULL, NULL, NULL, '2023-12-14 08:42:45', '2023-12-14 08:42:45'),
(6, 'Moox Testuser', 'dev@moox.org', NULL, '$2y$10$lEVhRO6vJi.stWGfp7OzfOPvrBhZx.QCxsKcY89rN1Yr.VLxF5WQO', NULL, NULL, NULL, '2023-12-14 08:43:34', '2023-12-14 08:43:34'),
(7, 'Moox Customer', 'webdeveloper@heco.de', NULL, '$2y$10$fTxAmu3UTANd8mIQQHxQAu5qfVm9YBcqexFmhYLAsMez0YtFTVafO', NULL, NULL, NULL, '2023-12-14 08:44:35', '2023-12-14 08:44:35');


/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;