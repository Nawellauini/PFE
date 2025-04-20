<div class="rapport-details">
    <h2 class="text-center mb-4"><?= htmlspecialchars($rapport['titre']) ?></h2>
    
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">معلومات التقرير</h5>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>القسم:</strong> <?= htmlspecialchars($rapport['nom_classe']) ?></p>
                    <p><strong>المعلم:</strong> <?= htmlspecialchars($rapport['nom_professeur'] . ' ' . $rapport['prenom_professeur']) ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>تاريخ الإنشاء:</strong> <?= date('d/m/Y H:i', strtotime($rapport['date_creation'])) ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">التعليقات</h5>
            <p class="card-text"><?= nl2br(htmlspecialchars($rapport['commentaires'])) ?></p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title">التوصيات</h5>
            <p class="card-text"><?= nl2br(htmlspecialchars($rapport['recommandations'])) ?></p>
        </div>
    </div>

    <div class="text-center mt-4">
        <a href="generer_rapport.php?id=<?= $rapport['id'] ?>" class="btn btn-primary" target="_blank">
            <i class="fas fa-file-pdf"></i> تحميل PDF
        </a>
    </div>
</div> 