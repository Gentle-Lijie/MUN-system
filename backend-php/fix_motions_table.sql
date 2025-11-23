-- 修复Motions表结构
USE mun;

-- 删除旧表
DROP TABLE IF EXISTS Motions;

-- 重新创建带正确字段名的表
CREATE TABLE Motions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT,
    motion_type VARCHAR(100) NOT NULL,
    proposer_id INT,
    file_id INT,
    unit_time_seconds INT,
    total_time_seconds INT,
    speaker_list_id INT DEFAULT NULL,
    vote_required BOOLEAN DEFAULT FALSE,
    veto_applicable BOOLEAN DEFAULT FALSE,
    state ENUM ('passed', 'rejected', 'pending') DEFAULT 'pending',
    vote_result JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES Sessions (id),
    FOREIGN KEY (proposer_id) REFERENCES Delegates (id),
    FOREIGN KEY (file_id) REFERENCES Files (id),
    FOREIGN KEY (speaker_list_id) REFERENCES SpeakerLists (id)
);
