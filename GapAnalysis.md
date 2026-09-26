# Test Coverage Gap Analysis

## 📊 Current Status
- **Total Models**: 20
- **Models with Tests**: ~8 (40%)
- **Total Services**: 23
- **Services with Tests**: ~5 (22%)

---

## 🚨 Critical Gaps (High Priority)

### Services Without Tests

#### 1. `ProductPriceCalculator` (CRITICAL - No Test)
**File**: `app/Services/ProductPriceCalculator.php`
**Responsibility**: Calculates total job prices for cart items, applies modifiers, handles area-based pricing
**Why Critical**: Core pricing logic that affects every order
**Happy Path Needed**:
- Calculate price for fixed pricing model
- Calculate price for quantity pricing model with tiers
- Calculate price for area-based pricing
- Apply percentage/flat modifiers correctly

**Unhappy Path Needed**:
- Product with no pricing tiers
- Invalid quantity (0 or negative)
- Modifier that results in negative price
- Area calculation edge cases

#### 2. `ProductPricingService` (HIGH - No Test)
**File**: `app/Services/ProductPricingService.php`
**Responsibility**: Resolves tier prices based on quantity and SKU, falls back to category discounts
**Happy Path Needed**:
- Resolve price from pricing tier
- Fall back to category discount when no tier matches
- Use product override price when set

**Unhappy Path Needed**:
- Product with no pricing tiers or category
- Invalid quantity outside all tier ranges
- Category without quantity discounts

#### 3. `ProductStartingPriceService` (MEDIUM - Partial Coverage)
**File**: `app/Services/ProductStartingPriceService.php`
**Current Coverage**: Partially tested in `ProductPricingModelTest`
**Missing**:
- Edge cases for different pricing models
- Products with no tiers

#### 4. `QuantityDiscountService` (MEDIUM - Partial Coverage)
**File**: `app/Services/QuantityDiscountService.php`
**Current Coverage**: Tested in `QuantityDiscountServiceTest`
**Missing**:
- Category hierarchy traversal edge cases
- Invalid discount types

---

### Models Without Tests

#### 5. `PricingTier` (HIGH - No Test)
**File**: `app/Models/PricingTier.php`
**Responsibility**: Represents quantity-based pricing tiers
**Happy Path Needed**:
- Create tier with valid min/max quantities
- Validate price is positive
- Save to database

**Unhappy Path Needed**:
- Min quantity greater than max quantity
- Negative price
- Missing required fields

#### 6. `ProductSku` (MEDIUM - No Dedicated Test)
**File**: `app/Models/ProductSku.php`
**Responsibility**: Represents concrete variant combinations
**Happy Path Needed**:
- Create SKU with valid options
- Set availability status
- Set outlet pricing

**Unhappy Path Needed**:
- Invalid option references
- Out of stock quantity
- Outlet price higher than regular price

#### 7. `CategoryQuantityDiscount` (MEDIUM - No Test)
**File**: `app/Models/CategoryQuantityDiscount.php`
**Responsibility**: Fallback quantity discounts for categories
**Happy Path Needed**:
- Create discount with valid type and value
- Set description

**Unhappy Path Needed**:
- Invalid discount type
- Negative discount value

---

### Services Without Tests (Lower Priority)

#### 8. `ProductAdminUrlService` - Generate admin URLs for products
#### 9. `ProductAvailabilitySynchronizer` - Sync availability from NewWave
#### 10. `ProductImageSynchronizer` - Sync images with NewWave
#### 11. `ProductMetadataSynchronizer` - Sync metadata with NewWave
#### 12. `ProductSkuSynchronizer` - Sync SKU data with NewWave
#### 13. `ProductMediaSyncService` - Sync local/remote media
#### 14. `OrderNotificationService` - Send order notifications
#### 15. `OrderPaymentNotificationService` - Send payment notifications

---

## 🧪 Unhappy Path Gaps (Across All Tests)

### Common Missing Scenarios:
1. **Empty cart checkout** - Partially covered in CheckoutTest
2. **Invalid product data** - Rarely tested
3. **Out of stock products** - Not systematically tested
4. **Price calculation edge cases**:
   - Zero quantity
   - Very large quantities
   - Prices at tier boundaries
5. **Authorization failures** - Limited coverage
6. **Database constraint violations** - Not tested

---

## 📝 Recommendations

### Phase 1: Critical Tests (Create Immediately)
1. `ProductPriceCalculatorTest` - Happy + unhappy paths for all pricing models
2. `ProductPricingServiceTest` - Price resolution with fallbacks
3. `PricingTierTest` - CRUD operations and validation
4. `ProductSkuTest` - Variant management and outlet pricing

### Phase 2: Synchronizer Tests
5. `ProductImageSynchronizerTest` - Image sync scenarios
6. `ProductMetadataSynchronizerTest` - Metadata sync scenarios
7. `ProductSkuSynchronizerTest` - SKU sync scenarios
8. `ProductAvailabilitySynchronizerTest` - Availability sync scenarios

### Phase 3: Lower Priority Services
9. `ProductAdminUrlServiceTest`
10. `OrderNotificationServiceTest`
11. `OrderPaymentNotificationServiceTest`
12. And remaining services...

---

## 🎯 Test Creation Strategy

For each new test file, follow this pattern:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\{ServiceName};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('it performs happy path scenario', function () {
    // Arrange
    // Act
    // Assert with assertSuccessful()
});

test('it handles edge case: [scenario]', function () {
    // Unhappy path test
});

test('it rejects invalid input: [scenario]', function () {
    // Validation error test
})->throws(\Exception::class, 'Expected error message');
```

---

## ✅ Existing Good Tests (Reference Patterns)

- `CartManagerTest` - Excellent happy + unhappy paths
- `CheckoutTest` - Good authorization tests
- `ProductPricingModelTest` - Good edge case coverage
- `MultiSkuQuantityDiscountTest` - Complex scenario testing
