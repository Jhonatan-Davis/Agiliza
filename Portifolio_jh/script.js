/* =========================================
   1. FUNDO CONSTELAÇÃO (NETWORKING)
   ========================================= */
const canvas = document.getElementById('tech-canvas');
const ctx = canvas.getContext('2d');

let particlesArray;
canvas.width = window.innerWidth;
canvas.height = window.innerHeight;

let mouse = { x: null, y: null, radius: (canvas.height/80) * (canvas.width/80) }

window.addEventListener('mousemove', function(event) {
    mouse.x = event.x;
    mouse.y = event.y;
});

class Particle {
    constructor(x, y, directionX, directionY, size, color) {
        this.x = x; this.y = y;
        this.directionX = directionX; this.directionY = directionY;
        this.size = size; this.color = color;
    }
    draw() {
        ctx.beginPath();
        ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2, false);
        ctx.fillStyle = '#3b82f6';
        ctx.fill();
    }
    update() {
        if (this.x > canvas.width || this.x < 0) this.directionX = -this.directionX;
        if (this.y > canvas.height || this.y < 0) this.directionY = -this.directionY;

        let dx = mouse.x - this.x;
        let dy = mouse.y - this.y;
        let distance = Math.sqrt(dx*dx + dy*dy);

        if (distance < mouse.radius + this.size){
            if (mouse.x < this.x && this.x < canvas.width - this.size * 10) this.x += 1;
            if (mouse.x > this.x && this.x > this.size * 10) this.x -= 1;
            if (mouse.y < this.y && this.y < canvas.height - this.size * 10) this.y += 1;
            if (mouse.y > this.y && this.y > this.size * 10) this.y -= 1;
        }
        this.x += this.directionX;
        this.y += this.directionY;
        this.draw();
    }
}

function init() {
    particlesArray = [];
    let numberOfParticles = (canvas.height * canvas.width) / 9000;
    for (let i = 0; i < numberOfParticles; i++) {
        let size = (Math.random() * 2) + 1;
        let x = (Math.random() * ((innerWidth - size * 2) - (size * 2)) + size * 2);
        let y = (Math.random() * ((innerHeight - size * 2) - (size * 2)) + size * 2);
        let directionX = (Math.random() * 0.5) - 0.25;
        let directionY = (Math.random() * 0.5) - 0.25;
        let color = '#3b82f6';
        particlesArray.push(new Particle(x, y, directionX, directionY, size, color));
    }
}

function connect() {
    let opacityValue = 1;
    for (let a = 0; a < particlesArray.length; a++) {
        for (let b = a; b < particlesArray.length; b++) {
            let distance = ((particlesArray[a].x - particlesArray[b].x) * (particlesArray[a].x - particlesArray[b].x))
            + ((particlesArray[a].y - particlesArray[b].y) * (particlesArray[a].y - particlesArray[b].y));
            
            if (distance < (canvas.width/7) * (canvas.height/7)) {
                opacityValue = 1 - (distance/20000);
                ctx.strokeStyle = 'rgba(59, 130, 246,' + opacityValue + ')';
                ctx.lineWidth = 1;
                ctx.beginPath();
                ctx.moveTo(particlesArray[a].x, particlesArray[a].y);
                ctx.lineTo(particlesArray[b].x, particlesArray[b].y);
                ctx.stroke();
            }
        }
    }
}

function animate() {
    requestAnimationFrame(animate);
    ctx.clearRect(0,0,innerWidth, innerHeight);
    for (let i = 0; i < particlesArray.length; i++) { particlesArray[i].update(); }
    connect();
}

window.addEventListener('resize', function(){
    canvas.width = innerWidth; canvas.height = innerHeight;
    mouse.radius = ((canvas.height/80) * (canvas.height/80));
    init();
});
window.addEventListener('mouseout', function(){ mouse.x = undefined; mouse.y = undefined; });
init(); animate();

/* =========================================
   2. EFEITO DE DIGITAÇÃO (TYPEWRITER)
   ========================================= */
const textElement = document.getElementById('typing-text');
const phrases = [
    "Desenvolvedor Full Stack",
    "Especialista em PHP & Node.js",
    "Criador de Soluções Web"
];
let phraseIndex = 0;
let charIndex = 0;
let isDeleting = false;

function typeWriter() {
    const currentPhrase = phrases[phraseIndex];
    if (isDeleting) {
        textElement.textContent = currentPhrase.substring(0, charIndex - 1);
        charIndex--;
    } else {
        textElement.textContent = currentPhrase.substring(0, charIndex + 1);
        charIndex++;
    }
    let typeSpeed = isDeleting ? 40 : 90;
    if (!isDeleting && charIndex === currentPhrase.length) {
        typeSpeed = 2000; isDeleting = true;
    } else if (isDeleting && charIndex === 0) {
        isDeleting = false; phraseIndex = (phraseIndex + 1) % phrases.length; typeSpeed = 500;
    }
    setTimeout(typeWriter, typeSpeed);
}
document.addEventListener('DOMContentLoaded', typeWriter);

/* =========================================
   3. EFEITO 3D NOS ELEMENTOS (TILT UNIVERSAL)
   ========================================= */
// Seleciona tudo que tem o atributo 'data-tilt'
const tiltElements = document.querySelectorAll('[data-tilt]');

tiltElements.forEach(el => {
    el.addEventListener('mousemove', (e) => {
        const rect = el.getBoundingClientRect();
        const x = e.clientX - rect.left; 
        const y = e.clientY - rect.top;
        
        const centerX = rect.width / 2;
        const centerY = rect.height / 2;
        
        // Intensidade do efeito
        const rotateX = ((y - centerY) / centerY) * -10; 
        const rotateY = ((x - centerX) / centerX) * 10;

        el.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale3d(1.05, 1.05, 1.05)`;
    });

    el.addEventListener('mouseleave', () => {
        el.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) scale3d(1, 1, 1)';
        el.style.transition = 'transform 0.5s ease';
    });
    
    el.addEventListener('mouseenter', () => {
        el.style.transition = 'none'; // Remove delay ao entrar
    });
});
/* =========================================
   4. BOTÃO VOLTAR AO TOPO (HUB)
   ========================================= */
const backToHubBtn = document.getElementById('back-to-hub');

window.addEventListener('scroll', () => {
    // Se rolar mais que 500px para baixo, mostra o botão
    if (window.scrollY > 500) {
        backToHubBtn.classList.add('show');
    } else {
        // Se estiver no topo, esconde
        backToHubBtn.classList.remove('show');
    }
});
/* --- SISTEMA DE ABAS --- */
function openTab(evt, tabName) {
    // 1. Esconde todos os conteúdos e remove a classe de animação
    var tabContent = document.getElementsByClassName("tab-content");
    for (var i = 0; i < tabContent.length; i++) {
        tabContent[i].style.display = "none";
        tabContent[i].classList.remove("active-content");
    }

    // 2. Remove a classe "active" de todos os botões
    var tabLinks = document.getElementsByClassName("tab-btn");
    for (var i = 0; i < tabLinks.length; i++) {
        tabLinks[i].className = tabLinks[i].className.replace(" active", "");
    }

    // 3. Mostra o conteúdo atual e ADICIONA a classe que dispara a animação
    var selectedTab = document.getElementById(tabName);
    selectedTab.style.display = "block";
    
    // Pequeno "truque" de tempo para o navegador perceber a mudança e rodar a animação
    setTimeout(function() {
        selectedTab.classList.add("active-content");
    }, 10);

    // 4. Marca o botão clicado como ativo
    evt.currentTarget.className += " active";
}

// Garante que a primeira aba (Projetos) abra corretamente ao carregar a página
document.addEventListener("DOMContentLoaded", function() {
    document.querySelector(".tab-btn.active").click();
});