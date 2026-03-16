<!-- FOOTER -->
<footer>
  <div class="footer-inner">
    <div class="footer-grid">
      <div>
        <div class="footer-logo">
          <img src="assets/images/Blemhader.png" alt="Blemhader" class="footer-logo-img">
        </div>
        <div class="footer-desc">
          <span class="ar-text">نغطي آخر الأحداث السياسية والاقتصادية والاجتماعية على مدار الساعة.</span>
          <span class="fr-text">Nous couvrons l'actualité politique, économique et sociale 24h/24.</span>
        </div>
      </div>
      <div class="footer-col">
        <h4>
          <span class="ar-text">الأقسام</span>
          <span class="fr-text">Rubriques</span>
        </h4>
        <?php
        $lang = isset($current_lang) ? $current_lang : 'ar';
        $lang_q = '?lang=' . $lang;
        ?>
        <a href="index.php<?php echo $lang_q; ?>"><span class="ar-text">الرئيسية</span><span class="fr-text">Accueil</span></a>
        <a href="live.php<?php echo $lang_q; ?>"><span class="ar-text">البثوث المباشرة</span><span class="fr-text">Les directs</span></a>
        <a href="meetings.php<?php echo $lang_q; ?>"><span class="ar-text">لقاءات</span><span class="fr-text">Rencontres</span></a>
        <a href="news.php<?php echo $lang_q; ?>"><span class="ar-text">أخبار</span><span class="fr-text">Actualités</span></a>
        <a href="economy.php<?php echo $lang_q; ?>"><span class="ar-text">اقتصاد</span><span class="fr-text">Économie</span></a>
        <a href="documentaries.php<?php echo $lang_q; ?>"><span class="ar-text">وثائقيات</span><span class="fr-text">Documentaires</span></a>
      </div>
      <div class="footer-col">
        <h4>
          <span class="ar-text">روابط</span>
          <span class="fr-text">Liens</span>
        </h4>
        <a href="#"><span class="ar-text">من نحن</span><span class="fr-text">Qui sommes-nous</span></a>
        <a href="#"><span class="ar-text">اتصل بنا</span><span class="fr-text">Contactez-nous</span></a>
        <a href="#"><span class="ar-text">الإعلان</span><span class="fr-text">Publicité</span></a>
        <a href="#"><span class="ar-text">سياسة الخصوصية</span><span class="fr-text">Politique de confidentialité</span></a>
      </div>
      <div class="footer-col">
        <h4>
          <span class="ar-text">تابعنا</span>
          <span class="fr-text">Suivez-nous</span>
        </h4>
        <a href="#">Facebook</a>
        <a href="#">X (Twitter)</a>
        <a href="#">Instagram</a>
        <a href="#">YouTube</a>
        <a href="#">Telegram</a>
      </div>
    </div>
    <div class="footer-bottom">
      <span>
        <span class="ar-text">© 2026 Blemhader. جميع الحقوق محفوظة.</span>
        <span class="fr-text">© 2026 Blemhader. Tous droits réservés.</span>
      </span>
      <span>
        <span class="ar-text">تصميم وتطوير: فريق Blemhader</span>
        <span class="fr-text">Conception & développement : Équipe Blemhader</span>
      </span>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>
