<?php
$rootDir = str_replace("\\", "/", realpath($_SERVER["DOCUMENT_ROOT"]));
session_start();
// Gui tu /include/displayData-admin.php
/*
NHIỆM VỤ:
- Nếu Accept cho mượn thì
    - DOME Gửi 1 email tới là sau 2 tuần phải trả lại luận văn. 
    - DONE Update ngay muon luan van la thoi diem nhan Accept.
    - Từ 2 nút Accept và Unavailable trở thành nút Đã Nhận Lại Luận Văn. SỬ DỤNG query available
        - DONE Nút Đã Nhận Lại Luận Văn sẽ UPDATE ngày trả lại là ngày nhấn nút.
    - DONE UPDATE lai available cua table available = false
    - Xóa các đơn có cùng mlv
        - Khi ấn nút cho mượn lv thì xóa hết các đơn có cùng mlv và gửi email về với nội dung là:
        "luận văn bạn đăng kí đã được cho mượn, vui lòng đăng kí lv lại sau 2 tuần"
*/
if (isset($_SESSION['id'])) {
    $mlv = $_GET['mlv'];
    $f_email = $_GET['e'];
    // TINH NANG UPDATE lai available cua table available = false
    if (empty($mlv)) {
        echo "khong co gia tri ma luan van";
    } else {
        include_once "../Database/conn.php";
        // UPDATE 
        $available_false = $conn->prepare("UPDATE $a SET Available = FALSE WHERE LV_Ma = ?;");
        $available_false->bind_param('s', $mlv);
        if (!$available_false->execute()) {
            echo "Ma luan van khong ton tai";
        } else {
            /* TINH NANG:
            - Nếu Accept cho mượn thì
                - DOME Gửi 1 email tới là sau 2 tuần phải trả lại luận văn. 
                - DONE Update ngay muon luan van la thoi diem nhan Accept.
            */
            // Lay currentDate YYYY-MM-DD vi mysql khong the doc dinh dang DD-MM-YYYY nen khi UPDATE se bi loi 
            // ($ngayMuon = $conn->prepare("UPDATE formThongTin SET f_NgayMuon = ?, f_NgayTra = ? WHERE f_Ma_LV = ?;");)

            $currentDate = date("Y-m-d");
            $returnDate = date('Y-m-d', strtotime("+2 weeks"));
            include_once "../Database/conn.php";
            // Cap nhat ngay muon va ngay tra
            $ngayMuon = $conn->prepare("UPDATE formThongTin SET f_NgayMuon = ?, f_NgayTraDuKien = ? WHERE f_Ma_LV = ?;");
            $ngayMuon->bind_param('sss', $currentDate, $returnDate, $mlv);
            if (!$ngayMuon->execute()) {
                header("Location: admin?accept=failed");
                exit();
            } else {
                // 2 dong nay la cap nhat lai ngay thang cho nguoi dung de hieu:
                date_default_timezone_set("Asia/Ho_Chi_Minh");
                $now = date("d-m-Y h:i:sa");
                $currentDate = date("d-m-Y");
                $returnDate = date('d-m-Y', strtotime("+2 weeks"));
                include_once "$rootDir/include/send-email.php";
                $subject = "Ngay tra luan van";
                $body = "Bây giờ là " . $now . ". Bạn đã mượn luận văn có mã số " . $mlv . " vào ngày " . $currentDate . " hãy trả luận văn trước ngày " . $returnDate;
                // Hàm gửi email này rất tốn thời gian
                $email = sendEmail($e_user, $e_pwd, 'banhbeocodung00@gmail.com', $subject, $body, $f_email);
                if(!$email->send()){
                    header("Location: /admin/admin?sendEmail=failed");
                    exit();
                }else {
                    header("Location: /admin/admin?sendEmail=succeed");
                    exit();
                }
            }
        }
    }
} else {
    echo "invalid url";
}
