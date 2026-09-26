import { useState, useEffect, useMemo } from 'react';

/**
 * Shared hook for computing subject pricing with package discounts.
 * Used by both SubjectUpdateModal (existing students) and StudentRegistrationForm (new students).
 *
 * @param {string[]} selectedSubjects - Array of selected subject names
 * @param {object[]} availableSubjects - Array of subject objects with { name, price, ... }
 * @param {string|number} currentGrade - e.g. "Grade 7", "7", or 7
 * @param {string} medium - "english" | "tamil" | null
 * @returns {{ monthlyAmount, originalAmount, appliedPackage, packages, packagesLoading }}
 */
export default function useSubjectPricing(selectedSubjects, availableSubjects, currentGrade, medium) {
  const [packages, setPackages] = useState([]);
  const [packagesLoading, setPackagesLoading] = useState(true);

  // Fetch packages from API
  useEffect(() => {
    const fetchPackages = async () => {
      setPackagesLoading(true);
      try {
        const API_BASE_URL = import.meta.env.VITE_API_URL || '/api';
        const res = await fetch(`${API_BASE_URL}/packages`);
        if (res.ok) {
          const data = await res.json();
          setPackages(data);
        }
      } catch (err) {
        console.error('Failed to fetch packages:', err);
      } finally {
        setPackagesLoading(false);
      }
    };
    fetchPackages();
  }, []);

  const getGradeNumber = (gradeValue) => {
    if (!gradeValue) return null;
    if (typeof gradeValue === 'number') return gradeValue;
    const match = gradeValue.toString().match(/(\d+)/);
    return match ? parseInt(match[1], 10) : null;
  };

  const pricing = useMemo(() => {
    if (!selectedSubjects || selectedSubjects.length === 0) {
      return { monthlyAmount: 0, originalAmount: 0, appliedPackage: null };
    }

    const gradeNum = getGradeNumber(currentGrade);

    // Original Monthly Fee Calculation (without package)
    const originalMonthly = selectedSubjects.reduce((sum, subjectName) => {
      const subjectObj = availableSubjects.find(s => s.name === subjectName);
      return sum + (subjectObj ? parseFloat(subjectObj.price) : 0);
    }, 0);

    let mAmount = originalMonthly;
    let appliedPkg = null;

    // Find applicable packages for this grade and medium
    if (gradeNum && packages && packages.length > 0) {
      const applicablePkgs = packages.filter(p => {
        let grades = p.applicable_grades;
        if (typeof grades === 'string') {
          try { grades = JSON.parse(grades); } catch(e) { grades = []; }
        }
        const hasGrade = Array.isArray(grades) && grades.some(g => parseInt(g, 10) === gradeNum);
        const hasMedium = p.medium === 'both' || p.medium === medium;
        return hasGrade && hasMedium;
      });

      const allSubjPkg = applicablePkgs.find(p => p.type === 'all_subjects');
      if (allSubjPkg && availableSubjects.length > 0 && selectedSubjects.length === availableSubjects.length) {
        mAmount = parseFloat(allSubjPkg.package_price);
        appliedPkg = allSubjPkg;
      } else {
        const mainSubjPkg = applicablePkgs.find(p => p.type === 'main_subjects');
        // Apply main_subjects package when student selects 2+ subjects
        // and their total would exceed the package price
        if (mainSubjPkg && selectedSubjects.length >= 2) {
          const pkgBasePrice = parseFloat(mainSubjPkg.package_price);
          let price = pkgBasePrice;
          // If addon_price exists and they selected more than base subjects count,
          // calculate: how many subjects fit in the base package price?
          // Extra subjects beyond base count get addon pricing
          if (mainSubjPkg.addon_price && parseFloat(mainSubjPkg.addon_price) > 0) {
            const addonPrice = parseFloat(mainSubjPkg.addon_price);
            // Determine base subject count from the package
            const baseCount = Math.max(2, Math.floor(pkgBasePrice / (originalMonthly / selectedSubjects.length)));
            if (selectedSubjects.length > baseCount) {
              const extraCount = selectedSubjects.length - baseCount;
              price += extraCount * addonPrice;
            }
          }
          // Only apply if it actually saves money
          if (price < originalMonthly) {
            mAmount = price;
            appliedPkg = mainSubjPkg;
          }
        }
      }
    }

    return {
      monthlyAmount: mAmount,
      originalAmount: originalMonthly,
      appliedPackage: appliedPkg,
    };
  }, [selectedSubjects, availableSubjects, currentGrade, medium, packages]);

  return {
    ...pricing,
    packages,
    packagesLoading,
  };
}
