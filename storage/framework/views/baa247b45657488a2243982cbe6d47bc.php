

<?php $__env->startSection('title', 'System Configuration'); ?>

<?php $__env->startSection('content'); ?>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-base-content">System Configuration</h1>
        <p class="text-sm text-base-content/60 mt-1">Configure monitoring cycle interval, notification settings, and health
            check parameters</p>
    </div>

    <div class="card bg-base-100 shadow-sm max-w-lg">
        <div class="card-body">
            <form method="POST" action="<?php echo e(route('system-config.update')); ?>">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PUT'); ?>

                
                <div class="form-control mb-6">
                    <label class="label" for="cycle_interval_minutes">
                        <span class="label-text">Cycle Interval (minutes)</span>
                    </label>
                    <input type="number" name="cycle_interval_minutes" id="cycle_interval_minutes"
                        value="<?php echo e(old('cycle_interval_minutes', $cycleInterval)); ?>" class="input input-bordered w-full"
                        required min="5" max="1440" step="1">

                    <p class="text-sm text-base-content/70">
                        How often the system checks all websites. Range:
                        5–1440 minutes.

                    </p>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['cycle_interval_minutes'];
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
                    <label class="label" for="notification_cycle_threshold">
                        <span class="label-text">Notification Cycle Threshold</span>
                    </label>
                    <input type="number" name="notification_cycle_threshold" id="notification_cycle_threshold"
                        value="<?php echo e(old('notification_cycle_threshold', $notificationCycleThreshold)); ?>"
                        class="input input-bordered w-full" required min="1" max="100" step="1">
                    <p class="text-sm text-base-content/70">
                        Number of cycles between repeated down
                        notifications. Range: 1–100 cycles.

                    </p>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['notification_cycle_threshold'];
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

                
                <div class="divider text-sm text-base-content/60 mb-6">Health Check Settings</div>

                
                <div class="form-control mb-6">
                    <label class="label" for="connection_timeout_seconds">
                        <span class="label-text">Connection Timeout (seconds)</span>
                    </label>
                    <input type="number" name="connection_timeout_seconds" id="connection_timeout_seconds"
                        value="<?php echo e(old('connection_timeout_seconds', $connectionTimeout)); ?>"
                        class="input input-bordered w-full" required min="1" max="60" step="1">
                    <p class="text-sm text-base-content/70">
                        Max time to establish a TCP connection. If
                        exceeded, the site is unreachable. Range: 1–60 seconds.

                    </p>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['connection_timeout_seconds'];
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
                    <label class="label" for="response_timeout_seconds">
                        <span class="label-text">Response Timeout (seconds)</span>
                    </label>
                    <input type="number" name="response_timeout_seconds" id="response_timeout_seconds"
                        value="<?php echo e(old('response_timeout_seconds', $responseTimeout)); ?>" class="input input-bordered w-full"
                        required min="5" max="120" step="1">
                    <p class="text-sm text-base-content/70">
                        Max time to wait for a response after connecting.
                        Slow sites may timeout. Range: 5–120 seconds.

                    </p>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['response_timeout_seconds'];
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
                    <label class="label" for="concurrency_limit">
                        <span class="label-text">Concurrency Limit</span>
                    </label>
                    <input type="number" name="concurrency_limit" id="concurrency_limit"
                        value="<?php echo e(old('concurrency_limit', $concurrencyLimit)); ?>" class="input input-bordered w-full"
                        required min="5" max="100" step="1">


                    <p class="text-sm text-base-content/70">
                        Max simultaneous HTTP requests per batch. Higher =
                        faster but uses more resources. Range: 5–100.

                    </p>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['concurrency_limit'];
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

                <div class="flex items-center justify-end pt-4 border-t border-base-200">
                    <button type="submit" class="btn btn-primary">
                        Save Configuration
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\laragon\www\webstatus-v2\resources\views/system-config/index.blade.php ENDPATH**/ ?>