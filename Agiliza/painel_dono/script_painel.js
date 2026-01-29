document.addEventListener('DOMContentLoaded', () => {
    const menuToggle = document.getElementById('menu-toggle');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');

    // Função para abrir/fechar menu
    function toggleMenu(e) {
        if (e) e.stopPropagation(); // Evita cliques duplos
        sidebar.classList.toggle('active');
    }

    // Clique no botão hambúrguer
    if (menuToggle) {
        menuToggle.addEventListener('click', toggleMenu);
    }

    // Fechar o menu se clicar fora dele (na área cinza do conteúdo)
    if (mainContent) {
        mainContent.addEventListener('click', () => {
            if (sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
            }
        });
    }
    
    // (Opcional) Fechar menu ao clicar em um link (se for SPA behavior, aqui não precisa tanto)
});