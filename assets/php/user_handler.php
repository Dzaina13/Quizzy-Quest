<?php
class UserHandler {
    private $koneksi;
    
    public function __construct($koneksi) {
        $this->koneksi = $koneksi;
    }
    
    public function getUserStats() {
        try {
            // Total users
            $totalSql = "SELECT COUNT(*) as total FROM users";
            $totalResult = mysqli_query($this->koneksi, $totalSql);
            $total = mysqli_fetch_assoc($totalResult)['total'];
            
            // Active users (simulate - all users are active since we don't have status field)
            $active = $total;
            
            // Admin users (premium in display)
            $adminSql = "SELECT COUNT(*) as admin FROM users WHERE role = 'admin'";
            $adminResult = mysqli_query($this->koneksi, $adminSql);
            $premium = mysqli_fetch_assoc($adminResult)['admin'];
            
            // Today's registrations
            $todaySql = "SELECT COUNT(*) as today FROM users WHERE DATE(created_at) = CURDATE()";
            $todayResult = mysqli_query($this->koneksi, $todaySql);
            $today = mysqli_fetch_assoc($todayResult)['today'];
            
            return [
                'total' => $total,
                'active' => $active,
                'premium' => $premium,
                'today' => $today
            ];
            
        } catch (Exception $e) {
            return [
                'total' => 0,
                'active' => 0,
                'premium' => 0,
                'today' => 0
            ];
        }
    }
}
?>
