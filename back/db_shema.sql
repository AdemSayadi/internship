CREATE TABLE `users`(
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP(), `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP());
ALTER TABLE
    `users` ADD UNIQUE `users_email_unique`(`email`);
CREATE TABLE `repositories`(
    `id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `url` VARCHAR(500) NULL,
    `provider` ENUM('github', 'gitlab', 'manual') NULL,
    `user_id` BIGINT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL
);
CREATE TABLE `code_submissions`(
    `id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NULL,
    `language` VARCHAR(50) NULL,
    `code_content` LONGTEXT NULL,
    `file_path` VARCHAR(500) NULL,
    `repository_id` BIGINT NULL,
    `user_id` BIGINT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL
);
CREATE TABLE `reviews`(
    `id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `code_submission_id` BIGINT NULL,
    `overall_score` INT NULL,
    `complexity_score` INT NULL,
    `security_score` INT NULL,
    `maintainability_score` INT NULL,
    `bug_count` INT NULL,
    `ai_summary` TEXT NULL,
    `suggestions` JSON NULL,
    `status` ENUM('pending', 'completed', 'failed') NULL,
    `processing_time` INT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL
);
CREATE TABLE `notifications`(
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `review_id` BIGINT UNSIGNED NOT NULL,
    `message` TEXT NOT NULL,
    `read` BOOLEAN NULL DEFAULT 'DEFAULT FALSE',
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP());
ALTER TABLE
    `notifications` ADD CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY(`user_id`) REFERENCES `users`(`id`);
ALTER TABLE
    `code_submissions` ADD CONSTRAINT `code_submissions_language_foreign` FOREIGN KEY(`language`) REFERENCES `repositories`(`id`);
ALTER TABLE
    `reviews` ADD CONSTRAINT `reviews_complexity_score_foreign` FOREIGN KEY(`complexity_score`) REFERENCES `code_submissions`(`id`);
ALTER TABLE
    `users` ADD CONSTRAINT `users_updated_at_foreign` FOREIGN KEY(`updated_at`) REFERENCES `repositories`(`id`);