<layout name="default">
  <div class="bg-white border shadow card">
    <div class="card-body">
      <h2 class="card-title text-primary">Bestellung bestätigt</h2>
      <p class="text-sm text-gray-700">
        Hallo {{ name }},<br />
        danke für deine Bestellung bei Moox. Deine Bestellnummer ist <strong>#{{ order.id }}</strong>.
      </p>

      <div class="mt-4 text-white alert bg-secondary">
        Wir benachrichtigen dich, sobald dein Paket versendet wurde.
      </div>

      <a href="{{ order.url }}" class="inline-block mt-6 btn btn-primary">Zur Bestellung</a>
    </div>
  </div>
</layout>