<!-- User Comparisons -->
        <?php if (!empty($userComparisons)): ?>
        <div class="user-comparisons">
            <h3>Your Previous Comparisons</h3>
            <?php foreach ($userComparisons as $comparison): ?>
            <div class="comparison-item">
                <div class="comparison-header">
                    <span class="wallet-badge <?php echo $comparison['wallet_name']; ?>">
                        <?php
                        $wallet_names = ['esewa' => 'eSewa', 'khalti' => 'Khalti', 'ime_pay' => 'IME Pay'];
                        echo $wallet_names[$comparison['wallet_name']] ?? $comparison['wallet_name'];
                        ?>
                    </span>
                    <span class="comparison-date"><?php echo date('M d, Y', strtotime($comparison['created_at'])); ?></span>
                </div>
                <div style="margin-top: 10px;">
                    <strong>Overall Rating:</strong>
                    <div class="rating-stars" style="display: inline-block; margin-left: 10px;">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="star <?php echo ($i <= $comparison['overall_rating']) ? 'filled' : ''; ?>">★</span>
                        <?php endfor; ?>
                    </div>
                </div>
                <?php if (!empty($comparison['strengths'])): ?>
                    <p><strong>Strengths:</strong> <?php echo htmlspecialchars($comparison['strengths']); ?></p>
                <?php endif; ?>
                <?php if (!empty($comparison['weaknesses'])): ?>
                    <p><strong>Weaknesses:</strong> <?php echo htmlspecialchars($comparison['weaknesses']); ?></p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div style="text-align: center; margin-top: 40px;">
            <a href="dashboard.php" style="color: #667eea; text-decoration: none; font-weight: 500;">← Back to Dashboard</a>
        </div>
    </div>

    <script>
        // Star rating interaction
        document.querySelectorAll('.star-rating').forEach(rating => {
            const inputs = rating.querySelectorAll('input[type="radio"]');
            const labels = rating.querySelectorAll('label');

            labels.forEach((label, index) => {
                label.addEventListener('click', () => {
                    const value = 5 - index;
                    inputs[value - 1].checked = true;

                    // Update visual feedback
                    labels.forEach((l, i) => {
                        if (i <= index) {
                            l.style.color = '#FFD700';
                        } else {
                            l.style.color = '#ddd';
                        }
                    });
                });
            });
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const walletSelect = document.querySelector('select[name="wallet_name"]');
            const ratingInputs = document.querySelectorAll('input[type="radio"]:checked');

            if (!walletSelect.value) {
                alert('Please select a wallet to review.');
                e.preventDefault();
                return;
            }

            if (ratingInputs.length < 6) {
                alert('Please provide ratings for all aspects.');
                e.preventDefault();
                return;
            }
        });
    </script>
</body>
</html>
