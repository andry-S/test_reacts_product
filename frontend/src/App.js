import React, { useEffect, useState } from 'react';

const API_URL = 'https://api.eportoqu.id/api/index.php/products';

function App() {
  const [products, setProducts] = useState([]); 
  const [form, setForm] = useState({ name: '', price: '', stock: '' });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    fetch(API_URL)
      .then(res => res.json())
      .then(data => {
        if (Array.isArray(data)) {
          setProducts(data);
          setError('');
        } else {
          setProducts([]);
          setError('Data produk tidak valid dari server');
          console.error('Response API bukan array:', data);
        }
      })
      .catch(() => {
        setProducts([]);
        setError('Gagal memuat produk');
      });
  }, []);

  function handleChange(e) {
    const { name, value } = e.target;
    setForm(prev => ({ ...prev, [name]: value }));
  }

  function handleSubmit(e) {
    e.preventDefault();
    setError('');
    if (!form.name || !form.price || !form.stock) {
      setError('Semua field harus diisi');
      return;
    }
    const priceNum = Number(form.price);
    const stockNum = Number(form.stock);
    if (isNaN(priceNum) || isNaN(stockNum)) {
      setError('Price dan stock harus angka');
      return;
    }

    setLoading(true);
    fetch(API_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        name: form.name,
        price: priceNum,
        stock: stockNum
      }),
    })
      .then(res => {
        setLoading(false);
        if (!res.ok) throw new Error('Gagal tambah produk');
        return res.json();
      })
      .then(newProduct => {
        setProducts(prev => [...prev, newProduct]);
        setForm({ name: '', price: '', stock: '' });
      })
      .catch(err => setError(err.message));
  }

  return (
    <div style={{ maxWidth: 600, margin: 'auto', padding: 20 }}>
      <h1>Daftar Produk</h1>
      {error && <p style={{ color: 'red' }}>{error}</p>}
      {Array.isArray(products) && products.length > 0 ? (
        <ul>
          {products.map(p => (
            <li key={p.id}>
              {p.name} - Harga: Rp {p.price.toLocaleString()} - Stok: {p.stock}
            </li>
          ))}
        </ul>
      ) : (
        <p>Belum ada produk atau data produk kosong.</p>
      )}

      <h2>Tambah Produk</h2>
      <form onSubmit={handleSubmit}>
        <div>
          <label>Nama:</label><br />
          <input name="name" value={form.name} onChange={handleChange} />
        </div>
        <div>
          <label>Harga:</label><br />
          <input name="price" value={form.price} onChange={handleChange} />
        </div>
        <div>
          <label>Stok:</label><br />
          <input name="stock" value={form.stock} onChange={handleChange} />
        </div>
        <button type="submit" disabled={loading}>
          {loading ? 'Menambahkan...' : 'Tambah Produk'}
        </button>
      </form>
    </div>
  );
}

export default App;
