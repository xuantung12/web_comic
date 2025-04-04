let slideIndex = 0; // Khởi tạo chỉ mục cho slide
const slidesWrapper = document.querySelector('.slides-wrapper');
const featuredItems = document.querySelectorAll('.featured-item');
const itemsPerSlide = 7; // Số lượng truyện hiển thị cùng lúc
const totalItems = featuredItems.length; // Tổng số truyện

function showSlides() {
    // Mỗi lần chuyển chỉ qua 1 truyện, nhưng vẫn hiển thị 7 truyện cùng lúc
    slideIndex++;

    // Nếu vượt qua tổng số truyện - 7, quay lại bắt đầu từ đầu
    if (slideIndex > totalItems - itemsPerSlide) {
        slideIndex = 0; // Quay lại bắt đầu
    }

    // Tính toán dịch chuyển tương ứng với 1 truyện
    let offset = slideIndex * (100 / itemsPerSlide);
    slidesWrapper.style.transform = `translateX(-${offset}%)`; // Sửa 'styl' thành 'style'

    // Thiết lập thời gian chờ để chuyển slide tiếp theo
    setTimeout(showSlides, 2000); // Thay đổi slide mỗi 2 giây
}

// Khởi động slideshow
showSlides();



// Run the function on page load and when the window is resized
window.addEventListener('load', updateItemsDisplay);
window.addEventListener('resize', updateItemsDisplay);


document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.getElementById('search-input');
    const comicListContainer = document.getElementById('comic-list-container');
    
    searchInput.addEventListener('input', function() {
        const searchValue = searchInput.value.trim().toLowerCase(); // Loại bỏ khoảng trắng dư thừa
        const comics = comicListContainer.getElementsByClassName('comic-item');
        
        // Hiển thị tất cả các truyện trước khi bắt đầu tìm kiếm lại
        Array.from(comics).forEach(comic => {
            comic.style.display = 'block';
        });

        // Lọc truyện dựa trên giá trị tìm kiếm
        Array.from(comics).forEach(comic => {
            const comicTitle = comic.querySelector('.comic-title').textContent.toLowerCase();
            if (!comicTitle.includes(searchValue)) {
                comic.style.display = 'none'; // Ẩn truyện không phù hợp
            }
        });
    });
    
    // Khởi động slideshow ở đây
    startSlideshow();
});

function startSlideshow() {
    // Logic cho slideshow
}



