-- Run ONCE on an existing pasig_tugon database (phpMyAdmin > SQL tab).
-- New installs get this from pasig_tugon_schema.sql already.
USE pasig_tugon;

ALTER TABLE tbl_concerns
    MODIFY sector         ENUM('Health','Infrastructure','Crime','Environment') NULL,
    MODIFY priority_level ENUM('High','Medium','Low') NULL,
    MODIFY status         ENUM('ongoing','for_review','archived') NOT NULL DEFAULT 'ongoing';

-- Urgency keywords now live only in ml_engine/priority.py
DROP TABLE IF EXISTS tbl_urgency_keywords;
