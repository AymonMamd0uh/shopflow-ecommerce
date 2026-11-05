import { useEffect, useState } from "react";
import { ShoppingCart, Search, Package, LoaderCircle } from "lucide-react";
import api from "../services/api";
import Navbar from "../components/Navbar";
import { useCart } from "../context/useCart";
import { useNavigate } from "react-router-dom";
export default function Products() {
  const navigate = useNavigate();
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [search, setSearch] = useState("");
  const { addToCart } = useCart();
  const [addingId, setAddingId] = useState(null);
  const [cartMessage, setCartMessage] = useState("");
  useEffect(() => {
    const fetchProducts = async () => {
      try {
        const response = await api.get("/products");
        setProducts(response.data.data.data);
      } catch {
        setError("Unable to load products. Please try again.");
      } finally {
        setLoading(false);
      }
    };

    fetchProducts();
  }, []);

  const filteredProducts = products.filter((product) =>
    product.name.toLowerCase().includes(search.toLowerCase()),
  );
  const handleAddToCart = async (productId) => {
    if (!localStorage.getItem("token")) {
      navigate("/login");
      return;
    }

    try {
      setAddingId(productId);
      setCartMessage("");

      await addToCart(productId, 1);

      setCartMessage("Product added to cart successfully!");
    } catch (error) {
      setCartMessage(
        error.response?.data?.message || "Failed to add product to cart.",
      );
    } finally {
      setAddingId(null);
    }
  };
  return (
    <div className="min-h-screen bg-slate-50">
      <Navbar />
      <main className="mx-auto max-w-7xl px-6 py-12">
        <div className="mb-10">
          <p className="mb-3 font-semibold text-blue-600">OUR COLLECTION</p>
          <h2 className="text-4xl font-extrabold text-slate-900">
            Discover Products
          </h2>
          {cartMessage && (
            <p className="mb-5 rounded-xl bg-blue-50 p-3 text-sm text-blue-700">
              {cartMessage}
            </p>
          )}
          <p className="mt-3 text-slate-500">
            Find everything you need in one place.
          </p>
        </div>

        <div className="mb-8 flex max-w-md items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3">
          <Search className="text-slate-400" size={20} />
          <input
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="Search products..."
            className="w-full bg-transparent outline-none"
          />
        </div>

        {loading ? (
          <div className="flex justify-center py-20">
            <LoaderCircle className="animate-spin text-blue-600" size={36} />
          </div>
        ) : error ? (
          <p className="py-10 text-center text-red-500">{error}</p>
        ) : filteredProducts.length === 0 ? (
          <p className="py-10 text-center text-slate-500">No products found.</p>
        ) : (
          <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            {filteredProducts.map((product) => (
              <article
                key={product.id}
                className="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-lg"
              >
                <div className="flex h-56 items-center justify-center bg-gradient-to-br from-blue-50 to-slate-100">
                  <Package size={64} className="text-blue-300" />
                </div>

                <div className="p-5">
                  <p className="mb-2 text-sm font-medium text-blue-600">
                    {product.category?.name || "General"}
                  </p>

                  <h3 className="text-lg font-bold text-slate-900">
                    {product.name}
                  </h3>

                  <p className="mt-2 line-clamp-2 min-h-10 text-sm text-slate-500">
                    {product.description ||
                      "Discover this product from ShopFlow."}
                  </p>

                  <div className="mt-5 flex items-center justify-between">
                    <span className="text-xl font-extrabold text-slate-900">
                      {Number(product.price).toLocaleString("en-EG")} EGP
                    </span>

                    <span className="text-xs text-slate-500">
                      {product.stock_quantity > 0
                        ? `${product.stock_quantity} in stock`
                        : "Out of stock"}
                    </span>
                  </div>

                  <button
                    onClick={() => handleAddToCart(product.id)}
                    disabled={
                      product.stock_quantity <= 0 || addingId === product.id
                    }
                    className="mt-5 flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 py-3 font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-300"
                  >
                    <ShoppingCart size={18} />
                    {addingId === product.id ? "Adding..." : "Add to Cart"}
                  </button>
                </div>
              </article>
            ))}
          </div>
        )}
      </main>
    </div>
  );
}
