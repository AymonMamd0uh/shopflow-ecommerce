import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { Package, RefreshCw, Plus, Pencil, Trash2 } from "lucide-react";
import api from "../../services/api";

export default function AdminProducts() {
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [deletingId, setDeletingId] = useState(null);

  const handleDelete = async (product) => {
    const confirmed = window.confirm(
      `Are you sure you want to delete "${product.name}"?`,
    );

    if (!confirmed) return;

    setDeletingId(product.id);
    setError("");

    try {
      await api.delete(`/admin/products/${product.id}`);
      await fetchProducts();
    } catch (err) {
      setError(err.response?.data?.message || "Failed to delete product.");
    } finally {
      setDeletingId(null);
    }
  };
  const fetchProducts = async () => {
    setLoading(true);
    setError("");

    try {
      const response = await api.get("/products");
      const data = response.data.data;

      setProducts(Array.isArray(data) ? data : data?.data || []);
    } catch (err) {
      setError(err.response?.data?.message || "Failed to load products.");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchProducts();
  }, []);

  return (
    <div className="min-h-screen bg-slate-50">


      <main className="mx-auto max-w-7xl px-6 py-10">
        <div className="mb-8 flex flex-wrap items-center justify-between gap-4">
          <div>
            <div className="mb-2 flex items-center gap-2 text-blue-600">
              <Package size={22} />
              <span className="font-semibold">Administration</span>
            </div>

            <h1 className="text-3xl font-extrabold text-slate-900">
              Products Management
            </h1>

            <p className="mt-2 text-slate-500">
              Manage your store products and inventory.
            </p>
          </div>

          <div className="flex gap-3">
            <button
              onClick={fetchProducts}
              disabled={loading}
              className="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 font-semibold text-slate-700 hover:bg-slate-100"
            >
              <RefreshCw size={17} />
              Refresh
            </button>

            <Link
              to="/admin/products/create"
              className="flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-3 font-semibold text-white hover:bg-blue-700"
            >
              <Plus size={18} />
              Add Product
            </Link>
          </div>
        </div>

        {error && (
          <div className="mb-6 rounded-xl bg-red-50 p-4 text-red-700">
            {error}
          </div>
        )}

        <div className="mb-6 rounded-2xl border border-slate-200 bg-white p-6">
          <p className="text-sm font-medium text-slate-500">Total Products</p>
          <p className="mt-2 text-3xl font-extrabold text-slate-900">
            {products.length}
          </p>
        </div>

        <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white">
          {loading ? (
            <div className="p-10 text-center text-slate-500">
              Loading products...
            </div>
          ) : products.length === 0 ? (
            <div className="p-10 text-center text-slate-500">
              No products found.
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-left">
                <thead className="bg-slate-50 text-sm text-slate-500">
                  <tr>
                    <th className="px-6 py-4">Product</th>
                    <th className="px-6 py-4">SKU</th>
                    <th className="px-6 py-4">Price</th>
                    <th className="px-6 py-4">Stock</th>
                    <th className="px-6 py-4">Status</th>
                    <th className="px-6 py-4">Actions</th>
                  </tr>
                </thead>

                <tbody className="divide-y divide-slate-100">
                  {products.map((product) => (
                    <tr key={product.id} className="hover:bg-slate-50">
                      <td className="px-6 py-4 font-semibold text-slate-900">
                        {product.name}
                      </td>

                      <td className="px-6 py-4 text-sm text-slate-500">
                        {product.sku}
                      </td>

                      <td className="px-6 py-4 font-semibold text-slate-900">
                        {product.price} EGP
                      </td>

                      <td className="px-6 py-4 text-slate-700">
                        {product.stock_quantity}
                      </td>

                      <td className="px-6 py-4">
                        <span
                          className={`rounded-full px-3 py-1 text-xs font-bold ${
                            product.is_active
                              ? "bg-green-100 text-green-700"
                              : "bg-red-100 text-red-700"
                          }`}
                        >
                          {product.is_active ? "Active" : "Inactive"}
                        </span>
                      </td>

                      <td className="px-6 py-4">
                        <div className="flex items-center gap-2">
                          <Link
                            to={`/admin/products/${product.id}/edit`}
                            title="Edit product"
                            className="rounded-lg bg-blue-50 p-2 text-blue-600 hover:bg-blue-100"
                          >
                            <Pencil size={17} />
                          </Link>

                          <button
                            onClick={() => handleDelete(product)}
                            disabled={deletingId === product.id}
                            title="Delete product"
                            className="rounded-lg bg-red-50 p-2 text-red-600 hover:bg-red-100 disabled:opacity-50"
                          >
                            <Trash2 size={17} />
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </main>
    </div>
  );
}
