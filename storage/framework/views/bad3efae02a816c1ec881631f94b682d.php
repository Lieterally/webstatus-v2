<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Webstatus ITK</title>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
</head>

<body class="min-h-screen flex items-center justify-center bg-base-200 font-sans">
    <div class="w-full max-w-md mx-4">
        
        <div class="card bg-base-100 shadow-sm">
            <div class="card-body p-8">
                
                <div class="text-center mb-8">
                    <img src="<?php echo e(asset('images/Logo_ITK.webp')); ?>" alt="Institut Teknologi Kalimantan"
                        class="mx-auto h-20 mb-4">
                    <h1 class="text-2xl font-bold text-base-content">Webstatus</h1>
                    <p class="text-sm text-base-content/60 mt-1">Website Monitoring System</p>
                </div>

                
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($sessionExpired) && $sessionExpired): ?>
                    <div class="alert alert-warning mb-5">
                        <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 6a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 6zm0 9a1 1 0 100-2 1 1 0 000 2z"
                                clip-rule="evenodd" />
                        </svg>
                        <span>Your session has expired. Please log in again.</span>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($errors->has('login')): ?>
                    <div class="alert alert-error mb-5">
                        <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z"
                                clip-rule="evenodd" />
                        </svg>
                        <span><?php echo e($errors->first('login')); ?></span>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                
                <form method="POST" action="<?php echo e(route('login')); ?>" novalidate>
                    <?php echo csrf_field(); ?>

                    
                    <div class="form-control mb-5">
                        <label class="label" for="username">
                            <span class="label-text">Username</span>
                        </label>
                        <input type="text" name="username" id="username" value="<?php echo e(old('username')); ?>"
                            class="input input-bordered w-full" placeholder="Enter your username" required
                            minlength="3" maxlength="64" autocomplete="username" autofocus>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['username'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <label class="label">
                                <span class="label-text-alt text-error"><?php echo e($message); ?></span>
                            </label>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>

                    
                    <div class="form-control mb-6">
                        <label class="label" for="password">
                            <span class="label-text">Password</span>
                        </label>
                        <input type="password" name="password" id="password" class="input input-bordered w-full"
                            placeholder="Enter your password" required minlength="8" maxlength="128"
                            autocomplete="current-password">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <label class="label">
                                <span class="label-text-alt text-error"><?php echo e($message); ?></span>
                            </label>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>

                    
                    <button type="submit" class="btn btn-primary w-full">
                        Log In
                    </button>
                </form>
            </div>
        </div>

        
        <p class="text-center text-xs text-base-content/40 mt-6">
            &copy; <?php echo e(date('Y')); ?> Institut Teknologi Kalimantan
        </p>
    </div>
</body>

</html>
<?php /**PATH D:\laragon\www\webstatus-v2\resources\views/auth/login.blade.php ENDPATH**/ ?>