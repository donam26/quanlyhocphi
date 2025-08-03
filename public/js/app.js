// Fix hiển thị trạng thái enrolled trong bảng
document.addEventListener('DOMContentLoaded', function() {
    // Thay đổi tất cả badge "enrolled" thành "Đang học"
    document.querySelectorAll('.enrolled, .badge').forEach(function(element) {
        if (element.classList.contains('enrolled') || element.textContent.includes('enrolled')) {
            element.classList.add('bg-success');
            element.textContent = 'Đang học';
        }
    });

    // Observer để xử lý các phần tử được thêm vào DOM sau này
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1) { // Element node
                    const enrolledBadges = node.querySelectorAll('.enrolled, .badge');
                    enrolledBadges.forEach(function(badge) {
                        if (badge.classList.contains('enrolled') || badge.textContent.includes('enrolled')) {
                            badge.classList.add('bg-success');
                            badge.textContent = 'Đang học';
                        }
                    });
                }
            });
        });
    });

    observer.observe(document.body, { childList: true, subtree: true });
}); 