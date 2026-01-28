-- Migration: Add video support to posts table
-- Run this migration to add video_url column to posts

ALTER TABLE posts ADD COLUMN video_url VARCHAR(500) DEFAULT NULL AFTER image_url;
