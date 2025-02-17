function validateForm() {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (password !== confirmPassword) {
        alert('Passwords do not match!');
        return false;
    }
    
    if (password.length < 8) {
        alert('Password must be at least 8 characters long!');
        return false;
    }
    
    return true;
}

// Dashboard Deisgns

const heroImages = [
    './assets/b1.jpg',
    './assets/b2.jpg',
    './assets/b3.jpg',
];
let currentImage = 0;
const hero = document.getElementById('hero');

function changeHeroImage() {
    hero.style.backgroundImage = `url(${heroImages[currentImage]})`;
    currentImage = (currentImage + 1) % heroImages.length;
}

setInterval(changeHeroImage, 5000);
changeHeroImage();