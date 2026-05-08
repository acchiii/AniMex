

<?php $__env->startSection('content'); ?>
<div class="max-w-2xl mx-auto px-4 py-10">
    <div class="bg-white/80 dark:bg-[#0a0a0f]/80 backdrop-blur-lg border border-gray-200 dark:border-gray-800/60 rounded-2xl p-6">
        <div class="flex items-start justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold">Profile</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Update your details and verify your email.</p>
            </div>
        </div>

        <?php if(session('success')): ?>
            <div class="mb-4 rounded-lg border border-green-500/30 bg-green-500/10 px-4 py-3 text-green-600 dark:text-green-400">
                <?php echo e(session('success')); ?>

            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-4">
                <h2 class="font-semibold text-gray-900 dark:text-gray-100">Edit information</h2>

                <form method="POST" action="<?php echo e(route('profile.update')); ?>" class="space-y-4">
                    <?php echo csrf_field(); ?>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200" for="name">Name</label>
                        <input
                            id="name"
                            name="name"
                            value="<?php echo e(old('name', $user->name)); ?>"
                            required
                            class="mt-1 w-full rounded-xl border-gray-300 dark:border-gray-700 bg-white/60 dark:bg-white/5 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-purple-500/50"
                        >
                        <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="mt-1 text-sm text-red-500"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200" for="username">Username</label>
                        <input
                            id="username"
                            name="username"
                            value="<?php echo e(old('username', $user->username)); ?>"
                            class="mt-1 w-full rounded-xl border-gray-300 dark:border-gray-700 bg-white/60 dark:bg-white/5 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-purple-500/50"
                        >
                        <?php $__errorArgs = ['username'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="mt-1 text-sm text-red-500"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <button
                        type="submit"
                        class="w-full md:w-auto bg-purple-600 hover:bg-purple-700 text-white font-medium px-4 py-2.5 rounded-xl transition shadow-sm shadow-purple-600/20"
                    >
                        Save changes
                    </button>
                </form>
            </div>

            <div class="space-y-4">
                <h2 class="font-semibold text-gray-900 dark:text-gray-100">Email verification</h2>

                <div class="rounded-xl border border-gray-200 dark:border-gray-800/60 bg-white/60 dark:bg-white/5 p-4">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-300">Email</p>
                            <p class="font-medium"><?php echo e($user->email); ?></p>
                        </div>

                        <?php if($user->hasVerifiedEmail()): ?>
                            <span class="inline-flex items-center rounded-full bg-green-500/15 text-green-600 dark:text-green-400 px-3 py-1 text-xs font-semibold">Verified</span>
                        <?php else: ?>
                            <span class="inline-flex items-center rounded-full bg-amber-500/15 text-amber-600 dark:text-amber-400 px-3 py-1 text-xs font-semibold">Not verified</span>
                        <?php endif; ?>
                    </div>

                    <?php if(!$user->hasVerifiedEmail()): ?>
                        <form method="POST" action="<?php echo e(route('verification.resend')); ?>" class="mt-4">
                            <?php echo csrf_field(); ?>
                            <button
                                type="submit"
                                class="w-full bg-gray-900 hover:bg-black text-white font-medium px-4 py-2.5 rounded-xl transition"
                            >
                                Resend verification email
                            </button>
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                If you don’t see it, check your spam folder.
                            </p>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\AniMex\resources\views/profile.blade.php ENDPATH**/ ?>