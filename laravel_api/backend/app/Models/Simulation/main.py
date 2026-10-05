from Player import Player
from Team import Team
from CricketMatch import CricketMatch
from FootballMatch import FootballMatch
from League import League

def main():
    # --- Cricket Simulation ---
    # India Team
    c_p1 = Player("Kohli", 35, "Batsman", 95, 90)
    c_p2 = Player("Bumrah", 30, "Bowler", 92, 95)
    c_team1 = Team("India", [c_p1, c_p2])

    # England Team
    c_p3 = Player("Root", 33, "Batsman", 90, 88)
    c_p4 = Player("Anderson", 41, "Bowler", 85, 80)
    c_team2 = Team("England", [c_p3, c_p4])

    print("--- Cricket Match: India vs England ---")
    c_match = CricketMatch(c_team1, c_team2)
    c_match.play()
    print(f"Winner: {c_match.winner.name}")
    print(f"Team 1 Points: {c_team1.points:.2f}")
    print(f"Team 2 Points: {c_team2.points:.2f}")
    print(f"Total Points (approx 102): {c_team1.points + c_team2.points:.2f}")
    print(f"Player of the Match: {c_match.player_of_the_match.name}")

    # --- Football Simulation ---
    # Argentina Team
    f_p1 = Player("Messi", 36, "Forward", 98, 85, goals=2, assists=1, shots_on_target=4)
    f_p2 = Player("Martinez", 31, "Goalkeeper", 85, 90, saves=5)
    f_team1 = Team("Argentina", [f_p1, f_p2])

    # Portugal Team
    f_p3 = Player("Ronaldo", 39, "Forward", 97, 88, goals=1, shots_on_target=5)
    f_p4 = Player("Patricio", 36, "Goalkeeper", 82, 80, saves=3)
    f_team2 = Team("Portugal", [f_p3, f_p4])

    print("\n--- Football Match: Argentina vs Portugal ---")
    f_match = FootballMatch(f_team1, f_team2)
    f_match.play()
    print(f"Winner: {f_match.winner.name if f_match.winner else 'Draw'}")
    print(f"Argentina Points: {f_team1.points:.2f}")
    print(f"Portugal Points: {f_team2.points:.2f}")
    print(f"Player of the Match: {f_match.player_of_the_match.name}")

    # --- League ---
    print("\n--- League Standing: World Sports League ---")
    my_league = League("World Sports League", [c_team1, c_team2, f_team1, f_team2])
    my_league.add_match(c_match)
    my_league.add_match(f_match)
    
    league_winner = my_league.determine_winner()
    print(f"Grand Champion: {league_winner.name}")

if __name__ == "__main__":
    main()
