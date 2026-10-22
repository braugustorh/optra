<div>
    @if ($showModal)
        <style>
            /* Reset y Estilos Autónomos para evitar que Filament cape las clases de Tailwind */
            .optra-privacy-overlay {
                position: fixed !important;
                top: 0 !important;
                right: 0 !important;
                bottom: 0 !important;
                left: 0 !important;
                z-index: 999999 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                padding: 1.25rem !important;
                background-color: rgba(15, 23, 42, 0.88) !important;
                backdrop-filter: blur(8px) !important;
                -webkit-backdrop-filter: blur(8px) !important;
                box-sizing: border-box !important;
            }

            .optra-privacy-card {
                position: relative !important;
                width: 100% !important;
                max-width: 900px !important;
                height: 88vh !important;
                max-height: 88vh !important;
                display: flex !important;
                flex-direction: column !important;
                background-color: #ffffff !important;
                border-radius: 1rem !important;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.45) !important;
                border: 1px solid #e2e8f0 !important;
                overflow: hidden !important;
                box-sizing: border-box !important;
                animation: optraFadeIn 0.25s ease-out !important;
            }

            .dark .optra-privacy-card {
                background-color: #0f172a !important;
                border-color: #1e293b !important;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.85) !important;
            }

            @keyframes optraFadeIn {
                from { opacity: 0; transform: scale(0.98); }
                to { opacity: 1; transform: scale(1); }
            }

            /* Header Fijo */
            .optra-privacy-header {
                flex-shrink: 0 !important;
                padding: 0.9rem 1.35rem !important;
                background-color: #f8fafc !important;
                border-bottom: 1px solid #e2e8f0 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                flex-wrap: nowrap !important;
                gap: 0.85rem !important;
                box-sizing: border-box !important;
            }

            @media (max-width: 768px) {
                .optra-privacy-header {
                    flex-wrap: wrap !important;
                    gap: 0.65rem !important;
                }
            }

            .dark .optra-privacy-header {
                background-color: #0b1120 !important;
                border-bottom-color: #1e293b !important;
            }

            /* Contenedor de Scroll (Cuerpo) */
            .optra-privacy-body {
                flex: 1 1 auto !important;
                min-height: 0 !important;
                overflow-y: auto !important;
                padding: 1.5rem !important;
                background-color: #ffffff !important;
                color: #1e293b !important;
                box-sizing: border-box !important;
                scroll-behavior: smooth !important;
            }

            .dark .optra-privacy-body {
                background-color: #0f172a !important;
                color: #f1f5f9 !important;
            }

            /* Scrollbar estilizado */
            .optra-privacy-body::-webkit-scrollbar {
                width: 8px !important;
            }
            .optra-privacy-body::-webkit-scrollbar-track {
                background: #f1f5f9 !important;
                border-radius: 4px !important;
            }
            .dark .optra-privacy-body::-webkit-scrollbar-track {
                background: #1e293b !important;
            }
            .optra-privacy-body::-webkit-scrollbar-thumb {
                background: #0d9488 !important;
                border-radius: 4px !important;
            }
            .optra-privacy-body::-webkit-scrollbar-thumb:hover {
                background: #0f766e !important;
            }

            /* Footer Fijo */
            .optra-privacy-footer {
                flex-shrink: 0 !important;
                padding: 1rem 1.5rem !important;
                background-color: #f8fafc !important;
                border-top: 1px solid #e2e8f0 !important;
                display: flex !important;
                flex-direction: column !important;
                gap: 0.85rem !important;
                box-sizing: border-box !important;
            }

            .dark .optra-privacy-footer {
                background-color: #0b1120 !important;
                border-top-color: #1e293b !important;
            }

            /* Tipografía del Documento Legal */
            .optra-legal-doc {
                font-family: inherit !important;
                line-height: 1.65 !important;
            }
            .optra-legal-doc h3 {
                font-size: 1.25rem !important;
                font-weight: 800 !important;
                color: #0f172a !important;
                text-align: center !important;
                margin-top: 0.5rem !important;
                margin-bottom: 0.35rem !important;
                text-transform: uppercase !important;
                letter-spacing: -0.015em !important;
            }
            .dark .optra-legal-doc h3 {
                color: #ffffff !important;
            }
            .optra-legal-doc h4 {
                font-size: 1.05rem !important;
                font-weight: 700 !important;
                color: #0f766e !important;
                margin-top: 1.35rem !important;
                margin-bottom: 0.5rem !important;
                border-bottom: 2px solid #ccfbf1 !important;
                padding-bottom: 0.35rem !important;
            }
            .dark .optra-legal-doc h4 {
                color: #2dd4bf !important;
                border-bottom-color: #134e4a !important;
            }
            .optra-legal-doc p {
                font-size: 0.925rem !important;
                line-height: 1.65 !important;
                color: #334155 !important;
                margin-bottom: 0.85rem !important;
            }
            .dark .optra-legal-doc p {
                color: #cbd5e1 !important;
            }
            .optra-legal-doc ul, .optra-legal-doc ol {
                margin: 0.5rem 0 1rem 0 !important;
                padding-left: 1.5rem !important;
            }
            .optra-legal-doc ul {
                list-style-type: disc !important;
            }
            .optra-legal-doc ol {
                list-style-type: decimal !important;
            }
            .optra-legal-doc li {
                font-size: 0.9rem !important;
                line-height: 1.6 !important;
                color: #334155 !important;
                margin-bottom: 0.35rem !important;
            }
            .dark .optra-legal-doc li {
                color: #cbd5e1 !important;
            }
            .optra-legal-doc strong {
                font-weight: 700 !important;
                color: #0f172a !important;
            }
            .dark .optra-legal-doc strong {
                color: #ffffff !important;
            }

            /* Botones del Sistema */
            .optra-btn-primary {
                background-color: #0d9488 !important;
                color: #ffffff !important;
                font-weight: 600 !important;
                font-size: 0.875rem !important;
                padding: 0.65rem 1.35rem !important;
                border-radius: 0.625rem !important;
                border: none !important;
                cursor: pointer !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                gap: 0.5rem !important;
                transition: all 0.15s ease-in-out !important;
                box-shadow: 0 4px 6px -1px rgba(13, 148, 136, 0.3) !important;
            }
            .optra-btn-primary:hover:not(:disabled) {
                background-color: #0f766e !important;
                box-shadow: 0 6px 12px -2px rgba(13, 148, 136, 0.45) !important;
                transform: translateY(-1px) !important;
            }
            .optra-btn-primary:disabled {
                background-color: #94a3b8 !important;
                cursor: not-allowed !important;
                opacity: 0.65 !important;
                box-shadow: none !important;
                transform: none !important;
            }

            .optra-btn-outline-danger {
                background-color: transparent !important;
                color: #dc2626 !important;
                border: 1px solid #f87171 !important;
                font-weight: 600 !important;
                font-size: 0.875rem !important;
                padding: 0.65rem 1.25rem !important;
                border-radius: 0.625rem !important;
                cursor: pointer !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                gap: 0.5rem !important;
                transition: all 0.15s ease-in-out !important;
            }
            .optra-btn-outline-danger:hover {
                background-color: #fef2f2 !important;
                border-color: #dc2626 !important;
            }
            .dark .optra-btn-outline-danger {
                color: #f87171 !important;
                border-color: #7f1d1d !important;
            }
            .dark .optra-btn-outline-danger:hover {
                background-color: rgba(127, 29, 29, 0.25) !important;
            }

            /* Step Pills en el Header - Más compactos y en una sola línea */
            .optra-steps-nav {
                display: flex !important;
                align-items: center !important;
                gap: 0.25rem !important;
                flex-wrap: nowrap !important;
                flex-shrink: 0 !important;
            }
            .optra-step-pill {
                padding: 0.22rem 0.55rem !important;
                border-radius: 0.375rem !important;
                font-size: 0.685rem !important;
                font-weight: 600 !important;
                line-height: 1.15 !important;
                white-space: nowrap !important;
                cursor: pointer !important;
                border: 1px solid transparent !important;
                transition: all 0.15s ease !important;
                display: inline-flex !important;
                align-items: center !important;
                gap: 0.25rem !important;
                text-decoration: none !important;
            }
            .optra-step-pill.active {
                background-color: #0d9488 !important;
                color: #ffffff !important;
                box-shadow: 0 1px 3px rgba(13, 148, 136, 0.3) !important;
            }
            .optra-step-pill.inactive {
                background-color: #e2e8f0 !important;
                color: #475569 !important;
            }
            .optra-step-pill.inactive:hover {
                background-color: #cbd5e1 !important;
                color: #0f172a !important;
            }
            .dark .optra-step-pill.inactive {
                background-color: #1e293b !important;
                color: #94a3b8 !important;
            }
            .dark .optra-step-pill.inactive:hover {
                background-color: #334155 !important;
                color: #f8fafc !important;
            }
            .optra-step-divider {
                font-size: 0.65rem !important;
                opacity: 0.45 !important;
                user-select: none !important;
            }
        </style>

        <div
            id="privacy-notice-modal-wrapper"
            class="optra-privacy-overlay"
            wire:key="privacy-notice-modal-{{ $noticeId ?? 'default' }}"
            x-data="{
                scrollToTop() {
                    const el = this.$refs.scrollContainer;
                    if (el) {
                        el.scrollTop = 0;
                    }
                }
            }"
            x-init="scrollToTop()"
            x-on:privacy-notice-scroll-top.window="scrollToTop()"
        >
            <div
                class="optra-privacy-card"
                role="dialog"
                aria-modal="true"
                aria-labelledby="privacy-modal-title"
            >
                {{-- Banner de Modo Vista Previa o Modo Administrador --}}
                @if ($isPreview)
                    <div style="background-color: #f59e0b; color: #ffffff; padding: 0.4rem 1rem; font-size: 0.75rem; font-weight: 600; display: flex; align-items: center; justify-content: space-between; flex-shrink: 0;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <x-heroicon-m-eye style="width: 1rem; height: 1rem;" />
                            <span>MODO VISTA PREVIA: Así se mostrará a los colaboradores al iniciar sesión.</span>
                        </div>
                        <button
                            type="button"
                            wire:click="closePreview"
                            style="background: transparent; border: none; color: #ffffff; text-decoration: underline; cursor: pointer; font-size: 0.75rem; font-weight: 700;"
                        >
                            Cerrar vista previa
                        </button>
                    </div>
                @elseif ($canBypass)
                    <div style="background-color: #d97706; color: #ffffff; padding: 0.4rem 1rem; font-size: 0.75rem; font-weight: 600; display: flex; align-items: center; justify-content: space-between; flex-shrink: 0; border-bottom: 1px solid rgba(255,255,255,0.2);">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <x-heroicon-m-shield-check style="width: 1rem; height: 1rem; flex-shrink: 0;" />
                            <span>MODO ADMINISTRADOR: Tienes permisos para desactivar o corregir este aviso en el sistema.</span>
                        </div>
                        <button
                            type="button"
                            wire:click="skipNoticeForAdmin"
                            style="background: transparent; border: none; color: #ffffff; text-decoration: underline; cursor: pointer; font-size: 0.75rem; font-weight: 700;"
                        >
                            Modo Administrador Saltarse el Aviso
                        </button>
                    </div>
                @endif

                {{-- Header Fijo del Modal (Siempre visible) --}}
                <div class="optra-privacy-header">
                    <div style="display: flex; align-items: center; gap: 0.75rem; min-width: 0; flex: 1 1 auto;">
                        <div style="width: 2.35rem; height: 2.35rem; border-radius: 0.65rem; background-color: #ccfbf1; color: #0f766e; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <x-heroicon-o-shield-check style="width: 1.45rem; height: 1.45rem;" />
                        </div>
                        <div style="min-width: 0;">
                            <div style="display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap;">
                                <h2 id="privacy-modal-title" style="font-size: 1.05rem; font-weight: 800; margin: 0; line-height: 1.25; color: inherit;">
                                    {{ $headerTitle }}
                                </h2>
                                @if ($version)
                                    <span style="font-size: 0.65rem; font-weight: 600; padding: 0.1rem 0.45rem; border-radius: 9999px; background-color: #ccfbf1; color: #0f766e; border: 1px solid #99f6e4;">
                                        {{ $version }}
                                    </span>
                                @endif
                            </div>
                            <p style="font-size: 0.75rem; margin: 0.15rem 0 0 0; opacity: 0.75; line-height: 1.2;">
                                Conforme a la Ley Federal de Protección de Datos Personales en Posesión de los Particulares
                            </p>
                        </div>
                    </div>

                    {{-- Selector de Etapas en la Cabecera (Compacto en una sola línea) --}}
                    <div class="optra-steps-nav">
                        <button
                            type="button"
                            wire:click="goToInitial"
                            class="optra-step-pill {{ $step === 'initial' ? 'active' : 'inactive' }}"
                            title="Ir a Aviso Importante"
                        >
                            1. Aviso Importante
                        </button>
                        <span class="optra-step-divider">›</span>
                        <button
                            type="button"
                            wire:click="goToSimplified"
                            class="optra-step-pill {{ $step === 'simplified' ? 'active' : 'inactive' }}"
                            title="Ir a Aviso Simplificado"
                        >
                            2. Aviso Simplificado
                        </button>
                        <span class="optra-step-divider">›</span>
                        <button
                            type="button"
                            wire:click="goToExtended"
                            class="optra-step-pill {{ $step === 'extended' ? 'active' : 'inactive' }}"
                            title="Ir a Aviso Extendido"
                        >
                            3. Aviso Extendido
                        </button>
                    </div>
                </div>

                {{-- Cuerpo del Modal (Único elemento con Scroll) --}}
                <div x-ref="scrollContainer" class="optra-privacy-body">

                    {{-- ETAPA 1: AVISO IMPORTANTE --}}
                    @if ($step === 'initial')
                        <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                            {{-- Tarjeta Principal Destacada --}}
                            <div style="background: linear-gradient(135deg, rgba(204, 251, 241, 0.6) 0%, rgba(240, 253, 250, 0.4) 100%); border: 1px solid #99f6e4; border-radius: 0.875rem; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                                <div style="display: flex; gap: 1rem; align-items: flex-start;">
                                    <div style="width: 2.25rem; height: 2.25rem; border-radius: 0.625rem; background-color: #0d9488; color: #ffffff; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        <x-heroicon-o-information-circle style="width: 1.4rem; height: 1.4rem;" />
                                    </div>
                                    <div style="font-size: 0.95rem; line-height: 1.65; color: inherit;">
                                        {!! $initialHtml !!}
                                    </div>
                                </div>
                            </div>

                            {{-- Ficha Resumen del Responsable --}}
                            <div style="background-color: rgba(241, 245, 249, 0.6); border: 1px solid #e2e8f0; border-radius: 0.875rem; padding: 1.15rem;">
                                <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #0d9488; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.4rem;">
                                    <x-heroicon-m-building-office-2 style="width: 1rem; height: 1rem;" />
                                    <span>Datos del Responsable del Tratamiento</span>
                                </div>

                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 0.85rem;">
                                    <div>
                                        <span style="font-size: 0.75rem; font-weight: 600; opacity: 0.7; display: block;">Empresa Contratante (Responsable):</span>
                                        <strong style="font-size: 0.9rem; display: block; margin-top: 0.2rem;">
                                            {{ $resolvedData['company_name'] ?: 'No asignada' }}
                                        </strong>
                                    </div>
                                    <div>
                                        <span style="font-size: 0.75rem; font-weight: 600; opacity: 0.7; display: block;">Herramienta Tecnológica:</span>
                                        <strong style="font-size: 0.9rem; display: block; margin-top: 0.2rem;">
                                            {{ $resolvedData['provider_name'] ?: 'NetInnfode' }}
                                        </strong>
                                    </div>
                                    @if (!empty($resolvedData['address']))
                                        <div style="grid-column: 1 / -1; border-top: 1px solid rgba(226, 232, 240, 0.8); padding-top: 0.6rem; margin-top: 0.25rem;">
                                            <span style="font-size: 0.75rem; font-weight: 600; opacity: 0.7; display: block;">Domicilio Registrado:</span>
                                            <span style="font-size: 0.85rem; display: block; margin-top: 0.2rem;">
                                                {{ $resolvedData['address'] }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Botones de Navegación del Paso 1 --}}
                            <div style="display: flex; flex-direction: column; sm:flex-direction: row; gap: 0.75rem; justify-content: space-between; align-items: center; margin-top: 0.5rem; padding-top: 1rem; border-top: 1px solid #e2e8f0;">
                                <button
                                    type="button"
                                    wire:click="rejectAndLogout"
                                    class="optra-btn-outline-danger"
                                    style="width: 100%; sm:width: auto;"
                                >
                                    <x-heroicon-m-arrow-left-on-rectangle style="width: 1rem; height: 1rem;" />
                                    <span>No continuar y Cerrar Sesión</span>
                                </button>

                                <button
                                    type="button"
                                    wire:click="goToSimplified"
                                    class="optra-btn-primary"
                                    style="width: 100%; sm:width: auto;"
                                >
                                    <span>Leer Aviso de Privacidad Simplificado</span>
                                    <x-heroicon-m-arrow-right style="width: 1rem; height: 1rem;" />
                                </button>
                            </div>
                        </div>

                    {{-- ETAPA 2: AVISO SIMPLIFICADO --}}
                    @elseif ($step === 'simplified')
                        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                            {{-- Contenido del Aviso Simplificado --}}
                            <div class="optra-legal-doc">
                                {!! $simplifiedHtml !!}
                            </div>

                            {{-- Leyenda interactiva al final para ir al aviso extendido --}}
                            <div style="background: linear-gradient(135deg, rgba(238, 242, 255, 0.8) 0%, rgba(204, 251, 241, 0.6) 100%); border: 1px solid #99f6e4; border-radius: 0.875rem; padding: 1.15rem; display: flex; flex-direction: column; sm:flex-direction: row; align-items: center; justify-content: space-between; gap: 1rem;">
                                <div>
                                    <h5 style="margin: 0; font-size: 0.9rem; font-weight: 700; color: #0f766e; display: flex; align-items: center; gap: 0.4rem;">
                                        <x-heroicon-m-document-text style="width: 1.1rem; height: 1.1rem;" />
                                        ¿Desea consultar el Aviso de Privacidad Extendido (Integral)?
                                    </h5>
                                    <p style="margin: 0.25rem 0 0 0; font-size: 0.8rem; color: #475569;">
                                        Consulte el detalle completo sobre transferencias, medidas de seguridad y el procedimiento de ejercicio de Derechos ARCO.
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    wire:click="goToExtended"
                                    class="optra-btn-primary"
                                    style="padding: 0.5rem 1rem !important; font-size: 0.8rem !important; flex-shrink: 0;"
                                >
                                    <span>Leer Aviso Extendido</span>
                                    <x-heroicon-m-arrow-top-right-on-square style="width: 0.9rem; height: 0.9rem;" />
                                </button>
                            </div>
                        </div>

                    {{-- ETAPA 3: AVISO EXTENDIDO --}}
                    @elseif ($step === 'extended')
                        <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                            {{-- Barra Superior de Regreso al Simplificado --}}
                            <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 0.5rem; border-bottom: 1px solid #e2e8f0;">
                                <button
                                    type="button"
                                    wire:click="goToSimplified"
                                    style="background: transparent; border: none; color: #0d9488; font-size: 0.825rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 0.35rem; padding: 0;"
                                >
                                    <x-heroicon-m-arrow-left style="width: 1rem; height: 1rem;" />
                                    <span>Volver al Aviso Simplificado</span>
                                </button>
                                <span style="font-size: 0.75rem; font-weight: 600; opacity: 0.65;">
                                    Versión Extensa / Integral
                                </span>
                            </div>

                            {{-- Contenido del Aviso Extendido --}}
                            <div class="optra-legal-doc">
                                {!! $extendedHtml !!}
                            </div>

                            {{-- Leyenda al final del aviso extendido para regresar al simplificado --}}
                            <div style="background-color: rgba(241, 245, 249, 0.7); border: 1px solid #e2e8f0; border-radius: 0.875rem; padding: 1rem; display: flex; flex-direction: column; sm:flex-direction: row; align-items: center; justify-content: space-between; gap: 0.75rem;">
                                <span style="font-size: 0.8rem; color: #475569;">
                                    Ha finalizado la lectura del Aviso de Privacidad Extendido. Puede volver al formato simplificado o aceptar a continuación.
                                </span>
                                <button
                                    type="button"
                                    wire:click="goToSimplified"
                                    class="optra-btn-primary"
                                    style="padding: 0.45rem 0.95rem !important; font-size: 0.775rem !important; flex-shrink: 0;"
                                >
                                    <x-heroicon-m-arrow-left style="width: 0.9rem; height: 0.9rem;" />
                                    <span>Volver al Simplificado</span>
                                </button>
                            </div>
                        </div>
                    @endif

                </div>

                {{-- Footer Fijo de Aceptación (Visible en Simplificado y Extendido) --}}
                @if ($step !== 'initial')
                    <div class="optra-privacy-footer">
                        {{-- Casilla de Verificación (Checkbox) --}}
                        <label style="display: flex; align-items: flex-start; gap: 0.75rem; cursor: pointer; padding: 0.85rem; border-radius: 0.75rem; border: 1.5px solid {{ $acceptedCheckbox ? '#0d9488' : '#cbd5e1' }}; background-color: {{ $acceptedCheckbox ? 'rgba(204, 251, 241, 0.25)' : '#ffffff' }}; transition: all 0.15s ease;">
                            <input
                                type="checkbox"
                                wire:model.live="acceptedCheckbox"
                                style="width: 1.15rem; height: 1.15rem; margin-top: 0.15rem; border-radius: 0.25rem; accent-color: #0d9488; cursor: pointer; flex-shrink: 0;"
                            >
                            <span style="font-size: 0.85rem; font-weight: 600; line-height: 1.45; color: inherit;">
                                {{ $checkboxLabel }}
                            </span>
                        </label>

                        {{-- Botones de Acción Fijos --}}
                        <div style="display: flex; flex-direction: column; sm:flex-direction: row; justify-content: space-between; align-items: center; gap: 0.75rem;">
                            <button
                                type="button"
                                wire:click="rejectAndLogout"
                                class="optra-btn-outline-danger"
                                style="width: 100%; sm:width: auto;"
                            >
                                <x-heroicon-m-arrow-left-on-rectangle style="width: 1rem; height: 1rem;" />
                                <span>No acepto (Cerrar sesión)</span>
                            </button>

                            <button
                                type="button"
                                wire:click="acceptNotice"
                                @disabled(!$acceptedCheckbox)
                                class="optra-btn-primary"
                                style="width: 100%; sm:width: auto;"
                            >
                                <x-heroicon-m-check-circle style="width: 1.15rem; height: 1.15rem;" />
                                <span>Aceptar Aviso de Privacidad</span>
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
