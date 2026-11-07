import React from 'react'
import { Helmet } from 'react-helmet-async'
import { useSettings } from '../context/SettingsContext'
import Hero from '../components/Hero'
import Classes from '../components/Classes'
import WhyChooseUs from '../components/WhyChooseUs'
import MobileApp from '../components/MobileApp'
import Onboarding from '../components/Onboarding'
import StudentToolkit from '../components/StudentToolkit'
import StudentsParentsLoveUs from '../components/StudentsParentsLoveUs'

const HomePage = () => {
  const { getSetting } = useSettings()
  const siteName = getSetting('site_name', 'Institute Portal')
  const siteDescription = getSetting(
    'site_description',
    getSetting(
      'hero_description',
      `${siteName} offers structured classes with live online sessions, recorded lessons, and study materials.`
    )
  )

  return (
    <>
      <Helmet>
        <title>{siteName}</title>
        <meta name="description" content={siteDescription} />
      </Helmet>
      <div style={{ paddingTop: '7.5rem' }}>
        <Hero />
        <Classes />
        <WhyChooseUs />
        <MobileApp />
        <Onboarding />
        <StudentToolkit />
        <StudentsParentsLoveUs />
      </div>
    </>
  )
}

export default HomePage

