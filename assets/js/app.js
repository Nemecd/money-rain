/* ==========================================
        MONEY RAIN APP
========================================== */

document.addEventListener("DOMContentLoaded", () => {
    // Initialize AOS
    AOS.init({
        duration: 800,
        easing: "ease-in-out",
        once: true,
    });
});

window.addEventListener("load", () => {
    const loader = document.getElementById("loader");
    setTimeout(() => {
        loader.style.opacity = "0";
        loader.style.visibility = "hidden";
        setTimeout(() => {
            loader.remove();
        }, 600);
    }, 1000);
});

/* ==========================================
        NAVBAR SCROLL
========================================== */

const navbar = document.querySelector(".navbar");
window.addEventListener("scroll", () => {
    if (window.scrollY > 80) {
        navbar.classList.add("scrolled");
    } else {
        navbar.classList.remove("scrolled");
    }
});

/* ==========================================
        COUNTER ANIMATION
========================================== */

const counters = document.querySelectorAll(".counter");
const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
        if (entry.isIntersecting) {
            const counter = entry.target;
            const target = Number(counter.dataset.target);
            let count = 0;
            const speed = target / 120;
            const update = () => {
                count += speed;
                if (count < target) {
                    counter.innerText = Math.floor(count).toLocaleString();
                    requestAnimationFrame(update);
                } else {
                    counter.innerText = target.toLocaleString();
                }
            };
            update();
            observer.unobserve(counter);
        }
    });
});
counters.forEach((counter) => observer.observe(counter));
/*=============================
      BACK TO TOP
=============================*/

const topBtn = document.getElementById("backToTop");
window.addEventListener("scroll", () => {
    if (window.scrollY > 400) {
        topBtn.style.display = "flex";
    }
    else {
        topBtn.style.display = "none";
    }
});
topBtn.addEventListener("click", () => {
    window.scrollTo({
        top: 0,
        behavior: "smooth"
    });
});
// Custom Toggler
const toggler = document.querySelector(".custom-toggler");

if (toggler) {
    toggler.addEventListener("click", function () {
        this.classList.toggle("active");
    });
}
const navbarCollapse = document.getElementById("navbarNav");

if (navbarCollapse) {
    navbarCollapse.addEventListener("hidden.bs.collapse", () => {
        toggler.classList.remove("active");
    });
}