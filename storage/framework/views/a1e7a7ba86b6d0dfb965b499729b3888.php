

<?php $__env->startSection('title', 'Edit Telegram Target'); ?>

<?php $__env->startSection('content'); ?>
    <div class="mb-6">
        <a href="<?php echo e(route('telegram-targets.index')); ?>" class="btn btn-ghost btn-sm">&larr; Back to Telegram Targets</a>
        <h1 class="text-2xl font-bold text-base-content mt-2">Edit Telegram Target</h1>
    </div>

    <div class="card bg-base-100 shadow-sm max-w-lg">
        <div class="card-body">
            <form method="POST" action="<?php echo e(route('telegram-targets.update', $telegramTarget)); ?>">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PUT'); ?>

                <div class="form-control mb-4">
                    <label class="label" for="chat_id">
                        <span class="label-text">Chat ID</span>
                    </label>
                    <input type="text" name="chat_id" id="chat_id"
                        value="<?php echo e(old('chat_id', $telegramTarget->chat_id)); ?>"
                        class="input input-bordered w-full <?php $__errorArgs = ['chat_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> input-error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                        placeholder="e.g. 123456789" required autofocus maxlength="32">
                    <label class="label">
                        <span class="label-text-alt text-base-content/60">Numeric string, maximum 32 characters</span>
                    </label>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['chat_id'];
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

                <div class="form-control mb-4" x-data="{ isActive: <?php echo e(old('is_active', $telegramTarget->is_active ? '1' : '0') == '1' ? 'true' : 'false'); ?> }">
                    <label class="label">
                        <span class="label-text">Status</span>
                    </label>
                    <div class="flex items-center gap-3">
                        <input type="checkbox" class="toggle toggle-primary" :checked="isActive"
                            @change="isActive = $event.target.checked" aria-label="Toggle active status">
                        <span class="label-text" x-text="isActive ? 'Active' : 'Inactive'"></span>
                        <input type="hidden" name="is_active" :value="isActive ? '1' : '0'">
                    </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['is_active'];
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

                <div class="divider"></div>

                <div class="flex items-center justify-end gap-3">
                    <a href="<?php echo e(route('telegram-targets.index')); ?>" class="btn btn-ghost">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        Update
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\laragon\www\webstatus-v2\resources\views/telegram-targets/edit.blade.php ENDPATH**/ ?>