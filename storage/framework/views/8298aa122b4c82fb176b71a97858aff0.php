<!DOCTYPE html>
<html lang="en" data-has-votes="<?php echo e($totalVotes > 0 ? 'true' : 'false'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f7f7f2">
    <meta name="description" content="A small session-based poll for choosing your next favorite programming language.">
    <title>Pick Your Stack — a tiny voting app</title>
    <link rel="stylesheet" href="<?php echo e(asset('css/app.css')); ?>">
    <script>
        const navigation = performance.getEntriesByType('navigation')[0];
        if (navigation?.type === 'reload' && document.documentElement.dataset.hasVotes === 'true') {
            document.documentElement.classList.add('resetting-session');
            document.addEventListener('DOMContentLoaded', () => {
                document.getElementById('clear-session-on-refresh').submit();
            }, { once: true });
        }
    </script>
</head>
<body>
    <form method="post" action="<?php echo e(route('poll.clear-session')); ?>" id="clear-session-on-refresh" hidden>
        <?php echo csrf_field(); ?>
    </form>
    <div class="page-shell">
        <header class="site-header">
            <a class="brand" href="<?php echo e(route('poll.index')); ?>" aria-label="Pick Your Stack home">
                <span class="brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 32 32" fill="none"><path d="M7 8.5 13 14l-6 5.5M16 21h9" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" /></svg>
                </span>
                <span>little poll<span class="brand-period">.</span></span>
            </a>
            <a class="header-link" href="#poll">Make your pick <span aria-hidden="true">↘</span></a>
        </header>

        <main>
            <section class="hero" aria-labelledby="page-title">
                <div class="hero-copy">
                    <p class="eyebrow"><span class="eyebrow-dot" aria-hidden="true"></span> A tiny question for curious builders</p>
                    <h1 id="page-title">Every great project<br>starts with a <span>pick.</span></h1>
                    <p class="hero-description">No big debate. Just your gut feeling, a few good options, and a little friendly tally.</p>
                </div>
                <div class="hero-sticker" aria-hidden="true"><span class="sticker-spark">✳</span><span>made for<br>the makers</span><span class="sticker-arrow">↘</span></div>
            </section>

            <section class="app-layout" id="poll" aria-label="Programming language poll">
                <div class="poll-card">
                    <div class="card-topline">
                        <span class="poll-label"><span class="label-icon" aria-hidden="true">✳</span> THE COMMUNITY QUESTION</span>
                        <span class="session-badge"><span aria-hidden="true"></span> SESSION UNTIL REFRESH</span>
                    </div>

                    <?php if($showResults): ?>
                        <div class="poll-content results-content">
                            <div class="section-number">01 <span>/ YOUR PICK</span></div>
                            <h2><?php echo e($poll['question']); ?></h2>
                            <p class="poll-description">Your picks add up for this session. Refresh this page to clear the tally; nothing is saved long-term.</p>

                            <div class="results-list" aria-label="Your session tally">
                                <?php $__currentLoopData = $poll['options']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        $count = (int) ($votes[$key] ?? 0);
                                        $percentage = $totalVotes > 0 ? (int) round(($count / $totalVotes) * 100) : 0;
                                        $isSelected = $selected === $key;
                                    ?>
                                    <div class="result-row option-<?php echo e($option['class']); ?><?php echo e($isSelected ? ' is-selected' : ''); ?>">
                                        <div class="result-heading">
                                            <div class="result-name"><span class="option-dot" aria-hidden="true"></span><span><?php echo e($option['label']); ?></span><?php if($isSelected): ?><span class="your-pick">YOUR PICK</span><?php endif; ?></div>
                                            <span class="result-number"><?php echo e($count); ?> <span><?php echo e($count === 1 ? 'pick' : 'picks'); ?></span></span>
                                        </div>
                                        <div class="progress-track" role="progressbar" aria-label="<?php echo e($option['label']); ?> votes in your session" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo e($percentage); ?>"><span class="progress-fill" style="width: <?php echo e($percentage); ?>%"></span></div>
                                        <div class="result-foot"><span><?php echo e($option['detail']); ?></span><span><?php echo e($percentage); ?>%</span></div>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                            <div class="results-actions">
                                <p><strong><?php echo e($totalVotes); ?></strong> <?php echo e($totalVotes === 1 ? 'temporary pick' : 'temporary picks'); ?> <span>· cleared on refresh</span></p>
                                <div class="results-controls">
                                    <form method="post" action="<?php echo e(route('poll.vote')); ?>" class="vote-again-form">
                                        <?php echo csrf_field(); ?>
                                        <label class="sr-only" for="another-option">Choose another language to vote for</label>
                                        <select id="another-option" name="option">
                                            <?php $__currentLoopData = $poll['options']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($key); ?>"><?php echo e($option['label']); ?></option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                        <button class="vote-button" type="submit">Add pick <span aria-hidden="true">+</span></button>
                                    </form>
                                    <form method="post" action="<?php echo e(route('poll.clear-session')); ?>" class="clear-votes-form">
                                        <?php echo csrf_field(); ?>
                                        <button class="text-button" type="submit">Clear picks</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="poll-content">
                            <div class="section-number">01 <span>/ THE QUESTION</span></div>
                            <h2><?php echo e($poll['question']); ?></h2>
                            <p class="poll-description"><?php echo e($poll['description']); ?></p>

                            <?php if($errors->any()): ?>
                                <div class="error-message" role="alert">
                                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><p><?php echo e($error); ?></p><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            <?php endif; ?>

                            <form method="post" action="<?php echo e(route('poll.vote')); ?>" class="vote-form">
                                <?php echo csrf_field(); ?>
                                <fieldset>
                                    <legend class="sr-only">Choose your favorite programming language</legend>
                                    <div class="options-list">
                                        <?php $__currentLoopData = $poll['options']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <label class="option-card option-<?php echo e($option['class']); ?>">
                                                <input type="radio" name="option" value="<?php echo e($key); ?>" <?php if($selected === $key): echo 'checked'; endif; ?> required>
                                                <span class="custom-radio" aria-hidden="true"></span>
                                                <span class="option-copy"><span class="option-name"><?php echo e($option['label']); ?></span><span class="option-detail"><?php echo e($option['detail']); ?></span></span>
                                                <span class="option-tag"><?php echo e($option['tag']); ?></span>
                                            </label>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </div>
                                </fieldset>
                                <div class="form-footer">
                                    <span class="privacy-note"><span aria-hidden="true">◎</span> Session only · clears on refresh</span>
                                    <button class="vote-button" type="submit">Show my pick <span aria-hidden="true">↗</span></button>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>

                <aside class="side-column" aria-label="About this poll">
                    <div class="side-card note-card">
                        <span class="note-icon" aria-hidden="true">✳</span>
                        <p class="side-kicker">A NOTE FROM US</p>
                        <h3>There’s no wrong answer.</h3>
                        <p>Every language has its moment. Pick the one you’d be excited to open your editor for.</p>
                        <span class="handwritten">Trust your instinct <span aria-hidden="true">↗</span></span>
                    </div>
                    <div class="side-card stats-card">
                        <div class="stats-top"><span class="stats-icon" aria-hidden="true">◎</span><span>QUICK FACTS</span></div>
                        <div class="stat-line"><span>Options to choose from</span><strong><?php echo e(count($poll['options'])); ?></strong></div>
                        <div class="stat-line"><span>Your session picks</span><strong><?php echo e($totalVotes); ?></strong></div>
                        <p class="local-note"><span aria-hidden="true">⌁</span> Picks add up in your session and clear on refresh. No shared tally or database.</p>
                    </div>
                </aside>
            </section>
        </main>

        <footer class="site-footer"><span>MADE FOR THE LOVE OF MAKING THINGS</span><span>SESSION ONLY. CLEARED ON REFRESH. <span aria-hidden="true">✳</span></span></footer>
    </div>
</body>
</html>
<?php /**PATH C:\Users\DELL\Source\Learning\Laravel\resources\views/poll/index.blade.php ENDPATH**/ ?>