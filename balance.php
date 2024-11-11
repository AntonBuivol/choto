<?php

class Balance {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Метод зачисления средств на баланс
    public function deposit($userId, $amount) {
        if ($amount <= 0) {
            return ['error' => 'Amount must be greater than zero'];
        }

        // Проверяем, существует ли пользователь в базе
        $query = "SELECT balance FROM balances WHERE user_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Пользователь найден, обновляем баланс
            $query = "UPDATE balances SET balance = balance + ? WHERE user_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param('di', $amount, $userId);
        } else {
            // Пользователь не найден, создаем запись с новым балансом
            $query = "INSERT INTO balances (user_id, balance) VALUES (?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param('id', $userId, $amount);
        }

        if ($stmt->execute()) {
            return ['message' => 'Deposit successful'];
        } else {
            return ['error' => 'Failed to deposit'];
        }
    }

    // Метод списания средств с баланса
    public function withdraw($userId, $amount) {
        if ($amount <= 0) {
            return ['error' => 'Amount must be greater than zero'];
        }

        // Проверяем текущий баланс пользователя
        $query = "SELECT balance FROM balances WHERE user_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $currentBalance = $row['balance'];

            // Проверяем, достаточно ли средств для списания
            if ($currentBalance >= $amount) {
                $query = "UPDATE balances SET balance = balance - ? WHERE user_id = ?";
                $stmt = $this->db->prepare($query);
                $stmt->bind_param('di', $amount, $userId);

                if ($stmt->execute()) {
                    return ['message' => 'Withdrawal successful'];
                } else {
                    return ['error' => 'Failed to withdraw'];
                }
            } else {
                return ['error' => 'Insufficient balance'];
            }
        } else {
            return ['error' => 'User not found'];
        }
    }

    public function transfer($fromUserId, $toUserId, $amount) {
        if ($amount <= 0) {
            return ['error' => 'Amount must be greater than zero'];
        }

        $this->db->begin_transaction();

        try {
            $withdrawResult = $this->withdraw($fromUserId, $amount);
            if (isset($withdrawResult['error'])) {
                throw new Exception($withdrawResult['error']);
            }

            $depositResult = $this->deposit($toUserId, $amount);
            if (isset($depositResult['error'])) {
                throw new Exception($depositResult['error']);
            }

            $this->db->commit();
            return ['message' => 'Transfer successful'];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['error' => $e->getMessage()];
        }
    }

    public function getBalance($userId) {
        $query = "SELECT balance FROM balances WHERE user_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return ['balance' => $row['balance']];
        } else {
            return ['error' => 'User not found'];
        }
    }
}
?>
