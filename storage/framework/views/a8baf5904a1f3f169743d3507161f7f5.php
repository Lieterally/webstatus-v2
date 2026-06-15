

<?php $__env->startSection('title', 'Telegram Manager'); ?>

<?php $__env->startSection('content'); ?>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Telegram Manager</h1>
            <p class="text-sm text-base-content/60 mt-1">Manage Telegram notification targets</p>
        </div>
        <a href="<?php echo e(route('telegram-targets.create')); ?>" class="btn btn-primary btn-sm">
            Add Target
        </a>
    </div>

    <div class="card bg-base-100 shadow-sm">
        <div class="card-body p-0">
            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <thead>
                        <tr>
                            <th>Chat ID</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $targets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $target): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <tr>
                                <td class="font-mono"><?php echo e($target->chat_id); ?></td>
                                <td>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($target->is_active): ?>
                                        <span class="badge badge-success badge-sm">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-ghost badge-sm">Inactive</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                                <td class="text-right space-x-2">
                                    <a href="<?php echo e(route('telegram-targets.edit', $target)); ?>"
                                        class="btn btn-ghost btn-xs">Edit</a>
                                    <div class="inline" x-data="{ showDeleteModal: false }">
                                        <button @click="showDeleteModal = true" type="button"
                                            class="btn btn-ghost btn-xs text-error">Delete</button>

                                        
                                        <div x-show="showDeleteModal" x-cloak
                                            class="fixed inset-0 z-50 flex items-center justify-center"
                                            x-transition:enter="transition ease-out duration-200"
                                            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                            x-transition:leave="transition ease-in duration-150"
                                            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                                            
                                            <div class="fixed inset-0 bg-black/50" @click="showDeleteModal = false"></div>

                                            
                                            <div class="relative bg-base-100 rounded-lg shadow-xl p-6 w-full max-w-sm mx-4 z-10"
                                                x-transition:enter="transition ease-out duration-200"
                                                x-transition:enter-start="opacity-0 scale-95"
                                                x-transition:enter-end="opacity-100 scale-100"
                                                x-transition:leave="transition ease-in duration-150"
                                                x-transition:leave-start="opacity-100 scale-100"
                                                x-transition:leave-end="opacity-0 scale-95"
                                                @click.outside="showDeleteModal = false">
                                                <div class="flex items-center gap-3 mb-4">
                                                    <div
                                                        class="flex-shrink-0 w-10 h-10 rounded-full bg-error/10 flex items-center justify-center">
                                                        <svg class="w-5 h-5 text-error" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                                        </svg>
                                                    </div>
                                                    <div>
                                                        <h3 class="text-lg font-semibold text-base-content">Delete Telegram
                                                            Target</h3>
                                                        <p class="text-sm text-base-content/60">This action cannot be
                                                            undone.</p>
                                                    </div>
                                                </div>

                                                <p class="text-sm text-base-content/70 mb-6">
                                                    Are you sure you want to delete the Telegram target with Chat ID <strong
                                                        class="font-mono"><?php echo e($target->chat_id); ?></strong>?
                                                </p>

                                                <div class="flex items-center justify-end gap-3">
                                                    <button @click="showDeleteModal = false" type="button"
                                                        class="btn btn-ghost btn-sm">
                                                        Cancel
                                                    </button>
                                                    <form action="<?php echo e(route('telegram-targets.destroy', $target)); ?>"
                                                        method="POST">
                                                        <?php echo csrf_field(); ?>
                                                        <?php echo method_field('DELETE'); ?>
                                                        <button type="submit" class="btn btn-error btn-sm">
                                                            Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            <tr>
                                <td colspan="3" class="text-center text-base-content/60">No Telegram targets found.</td>
                            </tr>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\laragon\www\webstatus-v2\resources\views/telegram-targets/index.blade.php ENDPATH**/ ?>