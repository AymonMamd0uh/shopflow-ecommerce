import { useCallback, useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { FolderOpen, RefreshCw, Plus, Pencil, Trash2 } from "lucide-react";
import api from "../../services/api";

export default function AdminCategories() {
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [deletingId, setDeletingId] = useState(null);

  const fetchCategories = useCallback(async () => {
    try {
      const response = await api.get("/categories");
      const data = response.data.data;

      setCategories(Array.isArray(data) ? data : data?.data || []);
    } catch (err) {
      setError(err.response?.data?.message || "Failed to load categories.");
    } finally {
      setLoading(false);
    }
  }, []);
  const handleRefresh = () => {
    setLoading(true);
    setError("");
    fetchCategories();
  };
  const handleDelete = async (category) => {
    const confirmed = window.confirm(
      `Are you sure you want to delete "${category.name}"?`,
    );

    if (!confirmed) return;

    setDeletingId(category.id);
    setError("");

    try {
      await api.delete(`/admin/categories/${category.id}`);
      await fetchCategories();
    } catch (err) {
      setError(err.response?.data?.message || "Failed to delete category.");
    } finally {
      setDeletingId(null);
    }
  };
  useEffect(() => {
    const timer = setTimeout(() => {
      fetchCategories();
    }, 0);

    return () => clearTimeout(timer);
  }, [fetchCategories]);

  return (
    <div className="min-h-screen bg-slate-50">
      <main className="mx-auto max-w-7xl px-6 py-10">
        <div className="mb-8 flex flex-wrap items-center justify-between gap-4">
          <div>
            <div className="mb-2 flex items-center gap-2 text-blue-600">
              <FolderOpen size={22} />
              <span className="font-semibold">Administration</span>
            </div>

            <h1 className="text-3xl font-extrabold text-slate-900">
              Categories Management
            </h1>

            <p className="mt-2 text-slate-500">Manage your store categories.</p>
          </div>

          <div className="flex gap-3">
            <button
              onClick={handleRefresh}
              disabled={loading}
              className="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 font-semibold text-slate-700 hover:bg-slate-100"
            >
              <RefreshCw size={17} />
              Refresh
            </button>

            <Link
              to="/admin/categories/create"
              className="flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-3 font-semibold text-white hover:bg-blue-700"
            >
              <Plus size={18} />
              Add Category
            </Link>
          </div>
        </div>

        {error && (
          <div className="mb-6 rounded-xl bg-red-50 p-4 text-red-700">
            {error}
          </div>
        )}

        <div className="mb-6 rounded-2xl border border-slate-200 bg-white p-6">
          <p className="text-sm font-medium text-slate-500">Total Categories</p>
          <p className="mt-2 text-3xl font-extrabold text-slate-900">
            {categories.length}
          </p>
        </div>

        <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white">
          {loading ? (
            <div className="p-10 text-center text-slate-500">
              Loading categories...
            </div>
          ) : categories.length === 0 ? (
            <div className="p-10 text-center text-slate-500">
              No categories found.
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-left">
                <thead className="bg-slate-50 text-sm text-slate-500">
                  <tr>
                    <th className="px-6 py-4">Category</th>
                    <th className="px-6 py-4">Slug</th>
                    <th className="px-6 py-4">Status</th>
                    <th className="px-6 py-4">Actions</th>
                  </tr>
                </thead>

                <tbody className="divide-y divide-slate-100">
                  {categories.map((category) => (
                    <tr key={category.id} className="hover:bg-slate-50">
                      <td className="px-6 py-4 font-semibold text-slate-900">
                        {category.name}
                      </td>

                      <td className="px-6 py-4 text-sm text-slate-500">
                        {category.slug}
                      </td>

                      <td className="px-6 py-4">
                        <span
                          className={`rounded-full px-3 py-1 text-xs font-bold ${
                            category.is_active
                              ? "bg-green-100 text-green-700"
                              : "bg-red-100 text-red-700"
                          }`}
                        >
                          {category.is_active ? "Active" : "Inactive"}
                        </span>
                      </td>

                      <td className="px-6 py-4">
                        <div className="flex items-center gap-2">
                          <Link
                            to={`/admin/categories/${category.id}/edit`}
                            title="Edit category"
                            className="rounded-lg bg-blue-50 p-2 text-blue-600 hover:bg-blue-100"
                          >
                            <Pencil size={17} />
                          </Link>

                          <button
                            onClick={() => handleDelete(category)}
                            disabled={deletingId === category.id}
                            title="Delete category"
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
