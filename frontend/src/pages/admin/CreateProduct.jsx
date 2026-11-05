import { useEffect, useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { ArrowLeft, Save } from "lucide-react";
import api from "../../services/api";

export default function CreateProduct() {
  const navigate = useNavigate();

  const [categories, setCategories] = useState([]);
  const [loadingCategories, setLoadingCategories] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState("");
  const [validationErrors, setValidationErrors] = useState({});

  const [form, setForm] = useState({
    category_id: "",
    name: "",
    slug: "",
    description: "",
    sku: "",
    price: "",
    stock_quantity: 0,
    is_active: true,
  });

  useEffect(() => {
    const fetchCategories = async () => {
      try {
        const response = await api.get("/categories");
        const data = response.data.data;

        setCategories(Array.isArray(data) ? data : data?.data || []);
      } catch {
        setError("Failed to load categories.");
      } finally {
        setLoadingCategories(false);
      }
    };

    fetchCategories();
  }, []);

  const handleChange = (e) => {
    const { name, value, type, checked } = e.target;

    setForm((prev) => ({
      ...prev,
      [name]: type === "checkbox" ? checked : value,
    }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    setSaving(true);
    setError("");
    setValidationErrors({});

    try {
      await api.post("/admin/products", {
        ...form,
        category_id: Number(form.category_id),
        price: Number(form.price),
        stock_quantity: Number(form.stock_quantity),
      });

      navigate("/admin/products");
    } catch (err) {
      if (err.response?.status === 422) {
        setValidationErrors(err.response.data.errors || {});
        setError("Please check the highlighted fields.");
      } else {
        setError(err.response?.data?.message || "Failed to create product.");
      }
    } finally {
      setSaving(false);
    }
  };

  const fieldClass =
    "w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100";

  const getError = (field) => validationErrors[field]?.[0];

  return (
    <div className="min-h-screen bg-slate-50">
      <main className="mx-auto max-w-3xl px-6 py-10">
        <Link
          to="/admin/products"
          className="mb-6 inline-flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-blue-600"
        >
          <ArrowLeft size={17} />
          Back to Products
        </Link>

        <div className="mb-8">
          <h1 className="text-3xl font-extrabold text-slate-900">
            Add New Product
          </h1>
          <p className="mt-2 text-slate-500">
            Enter the product information below.
          </p>
        </div>

        <form
          onSubmit={handleSubmit}
          className="space-y-6 rounded-2xl border border-slate-200 bg-white p-6"
        >
          {error && (
            <div className="rounded-xl bg-red-50 p-4 text-sm text-red-700">
              {error}
            </div>
          )}

          <div>
            <label className="mb-2 block text-sm font-semibold text-slate-700">
              Category *
            </label>

            <select
              name="category_id"
              value={form.category_id}
              onChange={handleChange}
              required
              disabled={loadingCategories}
              className={fieldClass}
            >
              <option value="">
                {loadingCategories
                  ? "Loading categories..."
                  : "Select category"}
              </option>

              {categories.map((category) => (
                <option key={category.id} value={category.id}>
                  {category.name}
                </option>
              ))}
            </select>

            {getError("category_id") && (
              <p className="mt-1 text-sm text-red-600">
                {getError("category_id")}
              </p>
            )}
          </div>

          <div>
            <label className="mb-2 block text-sm font-semibold text-slate-700">
              Product Name *
            </label>
            <input
              name="name"
              value={form.name}
              onChange={handleChange}
              required
              maxLength={255}
              className={fieldClass}
              placeholder="Wireless Headphones"
            />
            {getError("name") && (
              <p className="mt-1 text-sm text-red-600">{getError("name")}</p>
            )}
          </div>

          <div>
            <label className="mb-2 block text-sm font-semibold text-slate-700">
              Slug *
            </label>
            <input
              name="slug"
              value={form.slug}
              onChange={handleChange}
              required
              maxLength={255}
              className={fieldClass}
              placeholder="wireless-headphones"
            />
            {getError("slug") && (
              <p className="mt-1 text-sm text-red-600">{getError("slug")}</p>
            )}
          </div>

          <div>
            <label className="mb-2 block text-sm font-semibold text-slate-700">
              SKU *
            </label>
            <input
              name="sku"
              value={form.sku}
              onChange={handleChange}
              required
              maxLength={255}
              className={fieldClass}
              placeholder="WH-001"
            />
            {getError("sku") && (
              <p className="mt-1 text-sm text-red-600">{getError("sku")}</p>
            )}
          </div>

          <div>
            <label className="mb-2 block text-sm font-semibold text-slate-700">
              Description
            </label>
            <textarea
              name="description"
              value={form.description}
              onChange={handleChange}
              rows={4}
              className={fieldClass}
              placeholder="Product description..."
            />
            {getError("description") && (
              <p className="mt-1 text-sm text-red-600">
                {getError("description")}
              </p>
            )}
          </div>

          <div className="grid gap-6 sm:grid-cols-2">
            <div>
              <label className="mb-2 block text-sm font-semibold text-slate-700">
                Price (EGP) *
              </label>
              <input
                type="number"
                name="price"
                value={form.price}
                onChange={handleChange}
                required
                min="0.01"
                step="0.01"
                className={fieldClass}
                placeholder="1500.00"
              />
              {getError("price") && (
                <p className="mt-1 text-sm text-red-600">{getError("price")}</p>
              )}
            </div>

            <div>
              <label className="mb-2 block text-sm font-semibold text-slate-700">
                Stock Quantity
              </label>
              <input
                type="number"
                name="stock_quantity"
                value={form.stock_quantity}
                onChange={handleChange}
                min="0"
                step="1"
                className={fieldClass}
              />
              {getError("stock_quantity") && (
                <p className="mt-1 text-sm text-red-600">
                  {getError("stock_quantity")}
                </p>
              )}
            </div>
          </div>

          <label className="flex items-center gap-3 text-sm font-semibold text-slate-700">
            <input
              type="checkbox"
              name="is_active"
              checked={form.is_active}
              onChange={handleChange}
              className="h-5 w-5 accent-blue-600"
            />
            Active Product
          </label>

          {getError("is_active") && (
            <p className="text-sm text-red-600">{getError("is_active")}</p>
          )}

          <button
            type="submit"
            disabled={saving || loadingCategories || categories.length === 0}
            className="flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-3 font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
          >
            <Save size={18} />
            {saving ? "Saving Product..." : "Create Product"}
          </button>
        </form>
      </main>
    </div>
  );
}
