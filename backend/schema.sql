DROP DATABASE IF EXISTS bookmark_hub;
CREATE DATABASE bookmark_hub;
USE bookmark_hub;

CREATE TABLE member (
    member_id INT PRIMARY KEY AUTO_INCREMENT,
    nickname VARCHAR(50) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash CHAR(60) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE bookmark (
    bookmark_id INT PRIMARY KEY AUTO_INCREMENT,
    member_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    url VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_bookmark_member_id FOREIGN KEY (member_id) REFERENCES member(member_id) ON DELETE CASCADE
);

CREATE TABLE tag (
    tag_id INT PRIMARY KEY AUTO_INCREMENT,
    member_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tag_member_id FOREIGN KEY (member_id) REFERENCES member(member_id) ON DELETE CASCADE,
    UNIQUE KEY unique_tag (member_id, name)
);

CREATE TABLE bookmark_tag (
    bookmark_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (bookmark_id, tag_id),
    CONSTRAINT fk_bookmark_tag_bookmark_id FOREIGN KEY (bookmark_id) REFERENCES bookmark(bookmark_id) ON DELETE CASCADE,
    CONSTRAINT fk_bookmark_tag_tag_id FOREIGN KEY (tag_id) REFERENCES tag(tag_id) ON DELETE CASCADE
);
