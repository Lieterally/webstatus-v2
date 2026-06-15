<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'Webstatus'); ?> - ITK Webstatus</title>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::styles(); ?>

    <link rel="stylesheet" href="<?php echo e(asset('assets/font-awesome/css/all.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('assets/font-awesome/css/pro.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('assets/font-awesome/css/sharp-duotone-thin.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('assets/font-awesome/css/sharp-duotone-solid.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('assets/font-awesome/css/sharp-duotone-regular.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('assets/font-awesome/css/sharp-duotone-light.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('assets/font-awesome/css/sharp-thin.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('assets/font-awesome/css/sharp-solid.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('assets/font-awesome/css/sharp-regular.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('assets/font-awesome/css/sharp-light.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('assets/font-awesome/css/duotone-thin.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('assets/font-awesome/css/duotone-regular.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('assets/font-awesome/css/duotone-light.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('assets/font-awesome/css/woff2.css')); ?>">
</head>

<body class="min-h-screen bg-base-200 font-sans antialiased">
    <div class="drawer lg:drawer-open h-screen">
        <input id="sidebar-drawer" type="checkbox" class="drawer-toggle" />

        
        <div class="drawer-content flex flex-col overflow-y-auto h-screen">
            
            <div class="navbar bg-primary text-primary-content sticky top-0 z-30 shadow-md">
                <div class="flex-none lg:hidden">
                    <label for="sidebar-drawer" class="btn btn-square btn-ghost">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </label>
                </div>
                <div class="flex-1">
                    <a href="<?php echo e(route('dashboard')); ?>" class="flex items-center gap-2">
                        <img src="<?php echo e(asset('images/Logo_ITK_White.webp')); ?>" alt="ITK Logo" class="h-9 w-auto">
                        <span class="font-semibold text-lg hidden sm:inline">Webstatus</span>
                    </a>
                </div>
                <div class="flex-none">
                    <div class="flex items-center gap-3">

                        <div class="text-right">
                            <div class="text-sm font-medium">
                                <?php echo e(auth()->user()->username ?? ''); ?>

                            </div>
                            <div class="text-xs opacity-70">
                                <?php echo e(auth()->user()->role ?? ''); ?>

                            </div>
                        </div>

                        <form method="POST" action="<?php echo e(route('logout')); ?>">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="btn btn-outline btn-sm btn-primary-content">
                                Logout
                            </button>
                        </form>

                    </div>
                </div>
            </div>

            
            <main class="flex-1 p-4 md:p-6 lg:p-8">
                
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('success')): ?>
                    <div role="alert" class="alert alert-success mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span><?php echo e(session('success')); ?></span>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('error')): ?>
                    <div role="alert" class="alert alert-error mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span><?php echo e(session('error')); ?></span>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <?php echo $__env->yieldContent('content'); ?>
            </main>

            
            <footer class="footer footer-center bg-neutral text-neutral-content p-6">
                <div>
                    <img src="<?php echo e(asset('images/Logo_ITK.webp')); ?>" alt="ITK Logo" class="h-10 w-auto">
                    <p class="text-sm opacity-70">&copy; <?php echo e(date('Y')); ?> Institut Teknologi Kalimantan. All rights
                        reserved.</p>
                </div>
            </footer>
        </div>

        
        <div class="drawer-side z-40">
            <label for="sidebar-drawer" aria-label="close sidebar" class="drawer-overlay"></label>
            <aside class="bg-base-100 w-64 min-h-full border-r border-base-300">
                
                <div class="p-4 border-b border-base-300 lg:hidden">
                    <a href="<?php echo e(route('dashboard')); ?>" class="flex items-center gap-2">
                        <img src="<?php echo e(asset('images/Logo_ITK.webp')); ?>" alt="ITK Logo" class="h-8 w-auto">
                        <span class="font-semibold text-lg">Webstatus</span>
                    </a>
                </div>

                <ul class="menu p-4 gap-1">
                    
                    <li>
                        <a href="<?php echo e(route('dashboard')); ?>"
                            class="<?php echo e(request()->routeIs('dashboard') ? 'active' : ''); ?>">
                            <i class="fa-regular fa-home fa-lg fa-fw"></i>
                            Dashboard
                        </a>
                    </li>

                    
                    <li>
                        <a href="<?php echo e(route('sites.index')); ?>"
                            class="<?php echo e(request()->routeIs('sites.*') || request()->routeIs('categories.*') ? 'active' : ''); ?>">
                            <i class="fa-regular fa-globe fa-lg fa-fw"></i>
                            Website Manager
                        </a>
                    </li>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user() && auth()->user()->role === 'super_admin'): ?>
                        <li class="menu-title mt-4">Administration</li>

                        <li>
                            <a href="<?php echo e(route('users.index')); ?>"
                                class="<?php echo e(request()->routeIs('users.*') ? 'active' : ''); ?>">
                                <i class="fa-regular fa-users fa-lg fa-fw"></i>
                                User Manager
                            </a>
                        </li>

                        <li>
                            <a href="<?php echo e(route('it-staff.index')); ?>"
                                class="<?php echo e(request()->routeIs('it-staff.*') ? 'active' : ''); ?>">
                                <i class="fa-regular fa-id-card fa-lg fa-fw"></i>
                                IT Staff Manager
                            </a>
                        </li>

                        <li>
                            <a href="<?php echo e(route('telegram-targets.index')); ?>"
                                class="<?php echo e(request()->routeIs('telegram-targets.*') ? 'active' : ''); ?>">
                                <i class="fa-brands fa-telegram fa-lg fa-fw"></i>
                                Telegram Manager
                            </a>
                        </li>

                        <li>
                            <a href="<?php echo e(route('system-config.index')); ?>"
                                class="<?php echo e(request()->routeIs('system-config.*') ? 'active' : ''); ?>">
                                <i class="fa-regular fa-gear fa-lg fa-fw"></i>
                                System Config
                            </a>
                        </li>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <li class="menu-title mt-4">Settings</li>

                    <li>
                        <a href="<?php echo e(route('profile.password')); ?>"
                            class="<?php echo e(request()->routeIs('profile.*') ? 'active' : ''); ?>">
                            <i class="fa-regular fa-key fa-lg fa-fw"></i>
                            Change Password
                        </a>
                    </li>
                </ul>
            </aside>
        </div>
    </div>

    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::scripts(); ?>

    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>

</html>
<?php /**PATH D:\laragon\www\webstatus-v2\resources\views/layouts/app.blade.php ENDPATH**/ ?>