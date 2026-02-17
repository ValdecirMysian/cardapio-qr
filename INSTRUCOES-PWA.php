<?php
/**
 * ============================================================
 * INSTRUÇÕES PARA TRANSFORMAR EM PWA (Progressive Web App)
 * ============================================================
 * 
 * Adicione o código abaixo no seu cardapio.php para habilitar
 * a funcionalidade "Adicionar à tela inicial"
 * 
 * ARQUIVOS NECESSÁRIOS NA MESMA PASTA:
 * - manifest.json
 * - service-worker.js  
 * - offline.html
 * - /icons/ (pasta com os ícones)
 * - favicon.ico (opcional, mas recomendado)
 */

// ============================================================
// 1. ADICIONE ESTAS META TAGS NO <head> DO SEU HTML
//    (logo após as meta tags existentes, antes do CSS)
// ============================================================
?>

    <!-- ========== PWA - Progressive Web App ========== -->
    <!-- Manifest -->
    <link rel="manifest" href="manifest.json">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="icons/icon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="icons/icon-16x16.png">
    <link rel="shortcut icon" href="favicon.ico">
    
    <!-- Apple Touch Icons (iOS) -->
    <link rel="apple-touch-icon" href="icons/icon-192x192.png">
    <link rel="apple-touch-icon" sizes="152x152" href="icons/icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="icons/icon-180x180.png">
    <link rel="apple-touch-icon" sizes="167x167" href="icons/icon-167x167.png">
    
    <!-- Meta Tags PWA -->
    <meta name="theme-color" content="<?= !empty($farmacia['cor_primaria']) ? $farmacia['cor_primaria'] : '#0d6efd' ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="<?= e($farmacia['nome']) ?>">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="application-name" content="<?= e($farmacia['nome']) ?>">
    <meta name="msapplication-TileColor" content="<?= !empty($farmacia['cor_primaria']) ? $farmacia['cor_primaria'] : '#0d6efd' ?>">
    <meta name="msapplication-TileImage" content="icons/icon-144x144.png">
    <meta name="msapplication-config" content="browserconfig.xml">
    
    <!-- Splash Screens iOS (opcional mas melhora a experiência) -->
    <link rel="apple-touch-startup-image" href="icons/splash-640x1136.png" media="(device-width: 320px) and (device-height: 568px) and (-webkit-device-pixel-ratio: 2)">
    <link rel="apple-touch-startup-image" href="icons/splash-750x1334.png" media="(device-width: 375px) and (device-height: 667px) and (-webkit-device-pixel-ratio: 2)">
    <link rel="apple-touch-startup-image" href="icons/splash-1242x2208.png" media="(device-width: 414px) and (device-height: 736px) and (-webkit-device-pixel-ratio: 3)">
    <link rel="apple-touch-startup-image" href="icons/splash-1125x2436.png" media="(device-width: 375px) and (device-height: 812px) and (-webkit-device-pixel-ratio: 3)">
    <!-- ========== FIM PWA Meta Tags ========== -->

<?php
// ============================================================
// 2. ADICIONE ESTE SCRIPT ANTES DO </body> FINAL
//    (pode ser logo antes do fechamento </body>)
// ============================================================
?>

    <!-- ========== PWA Service Worker Registration ========== -->
    <script>
        // Registra o Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', async () => {
                try {
                    const registration = await navigator.serviceWorker.register('service-worker.js', {
                        scope: './'
                    });
                    console.log('✅ Service Worker registrado:', registration.scope);
                    
                    // Verifica atualizações
                    registration.addEventListener('updatefound', () => {
                        const newWorker = registration.installing;
                        console.log('🔄 Nova versão do Service Worker encontrada...');
                        
                        newWorker.addEventListener('statechange', () => {
                            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                // Nova versão disponível
                                showUpdateNotification();
                            }
                        });
                    });
                } catch (error) {
                    console.error('❌ Erro ao registrar Service Worker:', error);
                }
            });
        }
        
        // Notificação de atualização disponível
        function showUpdateNotification() {
            const toast = document.createElement('div');
            toast.innerHTML = `
                <div style="
                    position: fixed;
                    bottom: 80px;
                    left: 50%;
                    transform: translateX(-50%);
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 16px 24px;
                    border-radius: 12px;
                    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
                    z-index: 99999;
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    font-family: inherit;
                    animation: slideUp 0.3s ease;
                ">
                    <span>🚀 Nova versão disponível!</span>
                    <button onclick="window.location.reload()" style="
                        background: white;
                        color: #667eea;
                        border: none;
                        padding: 8px 16px;
                        border-radius: 8px;
                        font-weight: 600;
                        cursor: pointer;
                    ">Atualizar</button>
                </div>
            `;
            document.body.appendChild(toast);
        }
        
        // ========== PROMPT DE INSTALAÇÃO PWA ==========
        let deferredPrompt;
        const installBanner = document.createElement('div');
        
        window.addEventListener('beforeinstallprompt', (e) => {
            // Previne o prompt automático do Chrome
            e.preventDefault();
            deferredPrompt = e;
            
            // Mostra nosso banner customizado após 3 segundos
            setTimeout(() => {
                showInstallBanner();
            }, 3000);
        });
        
        function showInstallBanner() {
            // Não mostra se já foi instalado ou se já mostrou hoje
            if (window.matchMedia('(display-mode: standalone)').matches) return;
            if (localStorage.getItem('pwa-install-dismissed') === new Date().toDateString()) return;
            
            installBanner.innerHTML = `
                <div id="pwaInstallBanner" style="
                    position: fixed;
                    bottom: 0;
                    left: 0;
                    right: 0;
                    background: white;
                    padding: 16px 20px;
                    box-shadow: 0 -4px 20px rgba(0,0,0,0.15);
                    z-index: 99998;
                    display: flex;
                    align-items: center;
                    gap: 16px;
                    animation: slideUp 0.3s ease;
                    border-top: 3px solid var(--primary, #0d6efd);
                ">
                    <div style="
                        width: 50px;
                        height: 50px;
                        background: linear-gradient(135deg, var(--primary, #0d6efd) 0%, #764ba2 100%);
                        border-radius: 12px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        flex-shrink: 0;
                    ">
                        <i class="fas fa-mobile-alt" style="color: white; font-size: 24px;"></i>
                    </div>
                    <div style="flex: 1;">
                        <strong style="display: block; margin-bottom: 4px; color: #1f2937;">Instalar App</strong>
                        <span style="color: #6b7280; font-size: 14px;">Acesse mais rápido direto da sua tela inicial!</span>
                    </div>
                    <button onclick="installPWA()" style="
                        background: var(--primary, #0d6efd);
                        color: white;
                        border: none;
                        padding: 12px 20px;
                        border-radius: 10px;
                        font-weight: 600;
                        cursor: pointer;
                        white-space: nowrap;
                    ">Instalar</button>
                    <button onclick="dismissInstallBanner()" style="
                        background: none;
                        border: none;
                        color: #9ca3af;
                        font-size: 20px;
                        cursor: pointer;
                        padding: 8px;
                    ">×</button>
                </div>
            `;
            document.body.appendChild(installBanner);
            
            // Adiciona animação CSS
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideUp {
                    from { transform: translateY(100%); opacity: 0; }
                    to { transform: translateY(0); opacity: 1; }
                }
            `;
            document.head.appendChild(style);
        }
        
        async function installPWA() {
            if (!deferredPrompt) return;
            
            // Mostra o prompt de instalação
            deferredPrompt.prompt();
            
            // Espera a resposta do usuário
            const { outcome } = await deferredPrompt.userChoice;
            console.log(`Resultado da instalação: ${outcome}`);
            
            // Limpa a referência
            deferredPrompt = null;
            
            // Remove o banner
            dismissInstallBanner();
            
            if (outcome === 'accepted') {
                // Feedback visual de sucesso
                showToast('🎉 App instalado com sucesso!');
            }
        }
        
        function dismissInstallBanner() {
            const banner = document.getElementById('pwaInstallBanner');
            if (banner) {
                banner.style.animation = 'slideDown 0.3s ease forwards';
                setTimeout(() => installBanner.remove(), 300);
            }
            // Salva que o usuário dispensou hoje
            localStorage.setItem('pwa-install-dismissed', new Date().toDateString());
        }
        
        function showToast(message) {
            const toast = document.createElement('div');
            toast.style.cssText = `
                position: fixed;
                bottom: 100px;
                left: 50%;
                transform: translateX(-50%);
                background: #10b981;
                color: white;
                padding: 12px 24px;
                border-radius: 8px;
                font-weight: 500;
                z-index: 99999;
                animation: slideUp 0.3s ease;
            `;
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }
        
        // Detecta se já está em modo standalone (já instalado)
        if (window.matchMedia('(display-mode: standalone)').matches) {
            console.log('📱 App rodando em modo standalone (PWA instalado)');
        }
        
        // Detecta instalação bem-sucedida
        window.addEventListener('appinstalled', () => {
            console.log('✅ PWA foi instalado!');
            deferredPrompt = null;
        });
    </script>
    <!-- ========== FIM PWA Service Worker ========== -->

<?php
// ============================================================
// 3. ESTRUTURA DE ARQUIVOS NECESSÁRIA
// ============================================================
/*

📁 public/ (ou sua pasta web)
├── cardapio.php
├── manifest.json          ← Arquivo do PWA
├── service-worker.js      ← Service Worker
├── offline.html           ← Página offline
├── favicon.ico            ← Favicon principal
├── browserconfig.xml      ← Config para Windows (opcional)
└── 📁 icons/
    ├── icon-16x16.png
    ├── icon-32x32.png
    ├── icon-72x72.png
    ├── icon-96x96.png
    ├── icon-128x128.png
    ├── icon-144x144.png
    ├── icon-152x152.png
    ├── icon-167x167.png     ← iPad Pro
    ├── icon-180x180.png     ← iPhone
    ├── icon-192x192.png     ← Android
    ├── icon-384x384.png
    ├── icon-512x512.png     ← Splash screen
    └── screenshot-mobile.png ← Screenshot (opcional)

*/

// ============================================================
// 4. COMO GERAR OS ÍCONES
// ============================================================
/*

OPÇÃO 1 - FERRAMENTA ONLINE (MAIS FÁCIL):
-----------------------------------------
1. Acesse: https://realfavicongenerator.net/
2. Faça upload da logo da farmácia (PNG 512x512 ou maior)
3. Configure as cores (use a cor primária da farmácia)
4. Baixe o pacote gerado
5. Extraia na pasta do projeto

OPÇÃO 2 - FERRAMENTA ALTERNATIVA:
---------------------------------
1. Acesse: https://www.pwabuilder.com/imageGenerator
2. Faça upload da imagem
3. Baixe os ícones gerados

OPÇÃO 3 - GERAR COM CÓDIGO (ver arquivo generate-icons.php)

*/
?>
